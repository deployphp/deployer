<?php

declare(strict_types=1);

namespace Deployer\Executor;

use Deployer\Exception\Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ServerTest extends TestCase
{
    // ─── isCompleteRequest ───────────────────────────────────────

    #[Group('unit')]
    public function testIsCompleteRequestReturnsFalseForPartialHeaders(): void
    {
        self::assertFalse(Server::isCompleteRequest("POST /save HTTP/1.1\r\nContent"));
    }

    #[Group('unit')]
    public function testIsCompleteRequestReturnsFalseForHeadersWithoutBody(): void
    {
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: 13\r\n\r\n";
        self::assertFalse(Server::isCompleteRequest($request));
    }

    #[Group('unit')]
    public function testIsCompleteRequestReturnsFalseForPartialBody(): void
    {
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: 13\r\n\r\n{\"a\":";
        self::assertFalse(Server::isCompleteRequest($request));
    }

    #[Group('unit')]
    public function testIsCompleteRequestReturnsTrueForCompleteRequest(): void
    {
        $body = '{"key":"val"}';
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body;
        self::assertTrue(Server::isCompleteRequest($request));
    }

    #[Group('unit')]
    public function testIsCompleteRequestReturnsTrueWithExtraData(): void
    {
        $body = '{"a":1}';
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body . "extra";
        self::assertTrue(Server::isCompleteRequest($request));
    }

    #[Group('unit')]
    public function testIsCompleteRequestReturnsTrueWithNoContentLength(): void
    {
        $request = "GET /status HTTP/1.1\r\nHost: localhost\r\n\r\n";
        self::assertTrue(Server::isCompleteRequest($request));
    }

    #[Group('unit')]
    public function testIsCompleteRequestEmptyString(): void
    {
        self::assertFalse(Server::isCompleteRequest(''));
    }

    // ─── parseRequest ────────────────────────────────────────────

    #[Group('unit')]
    public function testParseRequestValid(): void
    {
        $body = '{"host":"web","config":{}}';
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body;

        [$path, $payload, $headers] = Server::parseRequest($request);

        self::assertSame('/save', $path);
        self::assertSame(['host' => 'web', 'config' => []], $payload);
        self::assertSame('application/json', $headers['content-type']);
    }

    #[Group('unit')]
    public function testParseRequestTrimsBodyToContentLength(): void
    {
        $body = '{"a":1}';
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body . "garbage";

        [$path, $payload, $headers] = Server::parseRequest($request);

        self::assertSame('/save', $path);
        self::assertSame(['a' => 1], $payload);
    }

    #[Group('unit')]
    public function testParseRequestCaseInsensitiveHeaders(): void
    {
        $body = '{"ok":true}';
        $request = "POST /load HTTP/1.1\r\ncontent-type: application/json\r\ncontent-length: " . strlen($body) . "\r\n\r\n" . $body;

        [$path, $payload, $headers] = Server::parseRequest($request);

        self::assertSame('/load', $path);
        self::assertSame(['ok' => true], $payload);
    }

    #[Group('unit')]
    public function testParseRequestThrowsOnMalformedRequestLine(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Malformed request line');

        Server::parseRequest("BADLINE\r\n\r\n");
    }

    #[Group('unit')]
    public function testParseRequestThrowsOnMissingHeaderTerminator(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no header terminator');

        Server::parseRequest("POST /save HTTP/1.1\r\nContent-Type: application/json");
    }

    #[Group('unit')]
    public function testParseRequestReturnsAuthorizationHeader(): void
    {
        $body = '{"a":1}';
        $request = "POST /load HTTP/1.1\r\nContent-Type: application/json\r\nAuthorization: Bearer secret123\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body;

        [$path, $payload, $headers] = Server::parseRequest($request);

        self::assertSame('/load', $path);
        self::assertSame('Bearer secret123', $headers['authorization']);
    }

    #[Group('unit')]
    public function testParseRequestThrowsOnInvalidContentType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('invalid Content-Type');

        Server::parseRequest("POST /save HTTP/1.1\r\nContent-Type: text/plain\r\n\r\nhello");
    }

    #[Group('unit')]
    public function testParseRequestThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        $body = 'not json';
        $request = "POST /save HTTP/1.1\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n\r\n" . $body;
        Server::parseRequest($request);
    }

    // ─── writeAll ────────────────────────────────────────────────

    #[Group('unit')]
    public function testWriteAllWritesToStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        Server::writeAll($stream, 'hello world');

        rewind($stream);
        self::assertSame('hello world', stream_get_contents($stream));
        fclose($stream);
    }

    #[Group('unit')]
    public function testWriteAllWritesLargeData(): void
    {
        $stream = fopen('php://memory', 'r+');
        $data = str_repeat('x', 100_000);
        Server::writeAll($stream, $data);

        rewind($stream);
        self::assertSame(100_000, strlen(stream_get_contents($stream)));
        fclose($stream);
    }

    // ─── Integration: real server lifecycle ──────────────────────

    private static function buildHttpRequest(string $method, string $path, array $payload, ?string $token = null): string
    {
        $body = json_encode($payload);
        $headers = "$method $path HTTP/1.1\r\n"
            . "Content-Type: application/json\r\n"
            . "Content-Length: " . strlen($body) . "\r\n";
        if ($token !== null) {
            $headers .= "Authorization: Bearer $token\r\n";
        }
        return $headers . "\r\n" . $body;
    }

    #[Group('integration')]
    public function testServerHandlesRequestAndResponds(): void
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $server = new Server('127.0.0.1', 0, $output);

        $receivedPath = null;
        $receivedPayload = null;

        $server->router(function (string $path, array $payload) use (&$receivedPath, &$receivedPayload) {
            $receivedPath = $path;
            $receivedPayload = $payload;
            return new Response(200, ['status' => 'ok']);
        });

        $clientSocket = null;

        $server->afterRun(function (int $port) use (&$clientSocket) {
            $clientSocket = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
            stream_set_blocking($clientSocket, false);
            fwrite($clientSocket, self::buildHttpRequest('POST', '/load', ['host' => 'web']));
        });

        $responseData = '';
        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSocket, &$responseData) {
            $tickCount++;
            if ($clientSocket) {
                $chunk = @fread($clientSocket, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $responseData .= $chunk;
                }
                if ($responseData !== '' && feof($clientSocket)) {
                    fclose($clientSocket);
                    $clientSocket = null;
                    $server->stop();
                    return;
                }
            }
            if ($tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertSame('/load', $receivedPath);
        self::assertSame(['host' => 'web'], $receivedPayload);
        self::assertStringContainsString('200 OK', $responseData);
        self::assertStringContainsString('"status": "ok"', $responseData);
    }

    #[Group('integration')]
    public function testServerReturns500OnRouterException(): void
    {
        $output = new BufferedOutput();
        $server = new Server('127.0.0.1', 0, $output);

        $server->router(function (string $path, array $payload) {
            throw new \RuntimeException('Something went wrong');
        });

        $clientSocket = null;
        $responseData = '';

        $server->afterRun(function (int $port) use (&$clientSocket) {
            $clientSocket = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
            stream_set_blocking($clientSocket, false);
            fwrite($clientSocket, self::buildHttpRequest('POST', '/crash', ['test' => true]));
        });

        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSocket, &$responseData) {
            $tickCount++;
            if ($clientSocket) {
                $chunk = @fread($clientSocket, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $responseData .= $chunk;
                }
                if ($responseData !== '' && feof($clientSocket)) {
                    fclose($clientSocket);
                    $clientSocket = null;
                    $server->stop();
                    return;
                }
            }
            if ($tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertStringContainsString('500 Internal Server Error', $responseData);
        self::assertStringContainsString('Something went wrong', $responseData);
    }

    #[Group('integration')]
    public function testServerHandlesMultipleConnections(): void
    {
        $output = new BufferedOutput();
        $server = new Server('127.0.0.1', 0, $output);

        $requestCount = 0;
        $server->router(function (string $path, array $payload) use (&$requestCount) {
            $requestCount++;
            return new Response(200, ['n' => $requestCount]);
        });

        $clientSockets = [];
        $server->afterRun(function (int $port) use (&$clientSockets) {
            for ($i = 0; $i < 2; $i++) {
                $sock = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
                stream_set_blocking($sock, false);
                fwrite($sock, self::buildHttpRequest('POST', '/test', ['i' => $i]));
                $clientSockets[] = $sock;
            }
        });

        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSockets) {
            $tickCount++;
            // Close any sockets that have received their response.
            foreach ($clientSockets as $key => $sock) {
                if ($sock === null) {
                    continue;
                }
                $chunk = @fread($sock, 65536);
                if (feof($sock)) {
                    fclose($sock);
                    $clientSockets[$key] = null;
                }
            }
            $allDone = true;
            foreach ($clientSockets as $sock) {
                if ($sock !== null) {
                    $allDone = false;
                }
            }
            if ($allDone || $tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertSame(2, $requestCount);
    }

    #[Group('integration')]
    public function testServerRejectsRequestWithoutToken(): void
    {
        $output = new BufferedOutput();
        $server = new Server('127.0.0.1', 0, $output);
        $server->setAuthToken('secret-token');

        $routerCalled = false;
        $server->router(function (string $path, array $payload) use (&$routerCalled) {
            $routerCalled = true;
            return new Response(200, ['ok' => true]);
        });

        $clientSocket = null;
        $responseData = '';

        $server->afterRun(function (int $port) use (&$clientSocket) {
            $clientSocket = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
            stream_set_blocking($clientSocket, false);
            // Send request without Authorization header.
            fwrite($clientSocket, self::buildHttpRequest('POST', '/load', ['host' => 'web']));
        });

        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSocket, &$responseData) {
            $tickCount++;
            if ($clientSocket) {
                $chunk = @fread($clientSocket, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $responseData .= $chunk;
                }
                if ($responseData !== '' && feof($clientSocket)) {
                    fclose($clientSocket);
                    $clientSocket = null;
                    $server->stop();
                    return;
                }
            }
            if ($tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertFalse($routerCalled, 'Router should not be called for unauthorized request');
        self::assertStringContainsString('403 Forbidden', $responseData);
    }

    #[Group('integration')]
    public function testServerRejectsRequestWithWrongToken(): void
    {
        $output = new BufferedOutput();
        $server = new Server('127.0.0.1', 0, $output);
        $server->setAuthToken('correct-token');

        $routerCalled = false;
        $server->router(function (string $path, array $payload) use (&$routerCalled) {
            $routerCalled = true;
            return new Response(200, ['ok' => true]);
        });

        $clientSocket = null;
        $responseData = '';

        $server->afterRun(function (int $port) use (&$clientSocket) {
            $clientSocket = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
            stream_set_blocking($clientSocket, false);
            fwrite($clientSocket, self::buildHttpRequest('POST', '/load', ['host' => 'web'], 'wrong-token'));
        });

        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSocket, &$responseData) {
            $tickCount++;
            if ($clientSocket) {
                $chunk = @fread($clientSocket, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $responseData .= $chunk;
                }
                if ($responseData !== '' && feof($clientSocket)) {
                    fclose($clientSocket);
                    $clientSocket = null;
                    $server->stop();
                    return;
                }
            }
            if ($tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertFalse($routerCalled, 'Router should not be called for wrong token');
        self::assertStringContainsString('403 Forbidden', $responseData);
    }

    #[Group('integration')]
    public function testServerAcceptsRequestWithCorrectToken(): void
    {
        $output = new BufferedOutput();
        $server = new Server('127.0.0.1', 0, $output);
        $server->setAuthToken('correct-token');

        $routerCalled = false;
        $server->router(function (string $path, array $payload) use (&$routerCalled) {
            $routerCalled = true;
            return new Response(200, ['ok' => true]);
        });

        $clientSocket = null;
        $responseData = '';

        $server->afterRun(function (int $port) use (&$clientSocket) {
            $clientSocket = stream_socket_client("tcp://127.0.0.1:$port", $errno, $errstr, 5);
            stream_set_blocking($clientSocket, false);
            fwrite($clientSocket, self::buildHttpRequest('POST', '/load', ['host' => 'web'], 'correct-token'));
        });

        $tickCount = 0;
        $server->ticker(function () use ($server, &$tickCount, &$clientSocket, &$responseData) {
            $tickCount++;
            if ($clientSocket) {
                $chunk = @fread($clientSocket, 65536);
                if ($chunk !== false && $chunk !== '') {
                    $responseData .= $chunk;
                }
                if ($responseData !== '' && feof($clientSocket)) {
                    fclose($clientSocket);
                    $clientSocket = null;
                    $server->stop();
                    return;
                }
            }
            if ($tickCount >= 30) {
                $server->stop();
            }
        });

        $server->run();

        self::assertTrue($routerCalled, 'Router should be called for authorized request');
        self::assertStringContainsString('200 OK', $responseData);
    }
}
