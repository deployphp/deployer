<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Closure;
use Deployer\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class Server
{
    private string $host;
    private int $port;
    private OutputInterface $output;
    private bool $stop = false;

    /**
     * @var ?resource
     */
    private $socket;

    /**
     * Each client is tracked with its socket, read buffer, and connection timestamp.
     * @var array<int, array{socket: resource, buffer: string, connectedAt: float}>
     */
    private array $clients = [];

    private int $nextClientId = 0;

    private Closure $afterCallback;
    private Closure $tickerCallback;
    private Closure $routerCallback;

    private ?string $authToken = null;

    /**
     * Timeout in seconds for idle client connections.
     */
    private float $clientTimeout = 30.0;

    private const REASON_PHRASES = [
        200 => 'OK',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];

    public function __construct(string $host, int $port, OutputInterface $output)
    {
        $this->host = $host;
        $this->port = $port;
        $this->output = $output;
    }

    public function run(): void
    {
        try {
            $this->socket = $this->createServerSocket();
            $this->updatePort();
            if ($this->output->isDebug()) {
                $this->output->writeln("[master] Starting server at http://{$this->host}:{$this->port}");
            }

            ($this->afterCallback)($this->port);

            while (true) {
                $this->acceptNewConnections();
                $this->handleClientRequests();

                // Prevent CPU exhaustion and 60fps ticker.
                usleep(16_000); // 16ms

                ($this->tickerCallback)();

                if ($this->stop) {
                    break;
                }
            }

            if ($this->output->isDebug()) {
                $this->output->writeln("[master] Stopping server at http://{$this->host}:{$this->port}");
            }
        } finally {
            foreach ($this->clients as $id => $client) {
                $this->closeClient($id);
            }
            if (isset($this->socket)) {
                fclose($this->socket);
            }
        }
    }

    /**
     * @return resource
     * @throws Exception
     */
    private function createServerSocket()
    {
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        if (!$server) {
            throw new Exception("Socket creation failed: $errstr ($errno)");
        }

        if (!stream_set_blocking($server, false)) {
            throw new Exception("Failed to set server socket to non-blocking mode");
        }

        return $server;
    }

    private function updatePort(): void
    {
        $name = stream_socket_get_name($this->socket, false);
        if ($name) {
            [, $port] = explode(':', $name);
            $this->port = (int) $port;
        } else {
            throw new Exception("Failed to get the assigned port");
        }
    }

    private function acceptNewConnections(): void
    {
        while ($newClientSocket = @stream_socket_accept($this->socket, 0)) {
            if (!stream_set_blocking($newClientSocket, false)) {
                fclose($newClientSocket);
                continue;
            }
            $id = $this->nextClientId++;
            $this->clients[$id] = [
                'socket' => $newClientSocket,
                'buffer' => '',
                'connectedAt' => microtime(true),
            ];
        }
    }

    private function handleClientRequests(): void
    {
        foreach ($this->clients as $id => $client) {
            $socket = $client['socket'];

            if (feof($socket)) {
                $this->closeClient($id);
                continue;
            }

            // Read available data into the buffer.
            $data = @fread($socket, 65536);
            if ($data !== false && $data !== '') {
                $this->clients[$id]['buffer'] .= $data;
            }

            // Check if we have a complete request.
            $buffer = $this->clients[$id]['buffer'];
            if (!self::isCompleteRequest($buffer)) {
                // Check for idle timeout.
                if (microtime(true) - $client['connectedAt'] > $this->clientTimeout) {
                    $this->closeClient($id);
                }
                continue;
            }

            // Process the complete request.
            try {
                [$path, $payload, $headers] = self::parseRequest($buffer);

                if ($this->authToken !== null) {
                    $provided = $headers['authorization'] ?? '';
                    if ($provided !== "Bearer {$this->authToken}") {
                        $this->sendResponse($socket, new Response(403, ['error' => 'Forbidden']));
                        continue;
                    }
                }

                $response = ($this->routerCallback)($path, $payload);
                $this->sendResponse($socket, $response);
            } catch (\Throwable $e) {
                $errorResponse = new Response(500, ['error' => $e->getMessage()]);
                try {
                    $this->sendResponse($socket, $errorResponse);
                } catch (\Throwable) {
                    // Best effort — socket may be broken.
                }
            } finally {
                $this->closeClient($id);
            }
        }
    }

    /**
     * Check if the buffer contains a complete HTTP request
     * (headers terminated by \r\n\r\n, and body length matches Content-Length).
     */
    public static function isCompleteRequest(string $buffer): bool
    {
        $headerEnd = strpos($buffer, "\r\n\r\n");
        if ($headerEnd === false) {
            return false;
        }

        $headers = substr($buffer, 0, $headerEnd);
        $bodyStart = $headerEnd + 4;

        // Extract Content-Length from headers.
        if (preg_match('/^Content-Length:\s*(\d+)/mi', $headers, $matches)) {
            $contentLength = (int) $matches[1];
            return strlen($buffer) - $bodyStart >= $contentLength;
        }

        // No Content-Length header — request is complete after headers.
        return true;
    }

    /**
     * Parse a complete HTTP request into path, payload, and headers.
     *
     * @return array{0: string, 1: mixed, 2: array<string, string>}
     */
    public static function parseRequest(string $request): array
    {
        $headerEnd = strpos($request, "\r\n\r\n");
        if ($headerEnd === false) {
            throw new Exception("Malformed request: no header terminator found");
        }

        $headerSection = substr($request, 0, $headerEnd);
        $body = substr($request, $headerEnd + 4);

        $lines = explode("\r\n", $headerSection);
        $requestLine = $lines[0];
        $parts = explode(' ', $requestLine);
        if (count($parts) !== 3) {
            throw new Exception("Malformed request line: $requestLine");
        }
        $path = $parts[1];

        $headers = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        if (empty($headers['content-type']) || $headers['content-type'] !== 'application/json') {
            throw new Exception("Malformed request: invalid Content-Type");
        }

        // Trim body to Content-Length if present (ignore trailing data).
        if (isset($headers['content-length'])) {
            $body = substr($body, 0, (int) $headers['content-length']);
        }

        $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        return [$path, $payload, $headers];
    }

    /**
     * @param resource $socket
     */
    private function sendResponse($socket, Response $response): void
    {
        $code = $response->getStatus();
        $reason = self::REASON_PHRASES[$code] ?? 'Unknown';
        $content = json_encode($response->getBody(), flags: JSON_PRETTY_PRINT);
        $data = "HTTP/1.1 $code $reason\r\n"
            . "Content-Type: application/json\r\n"
            . "Content-Length: " . strlen($content) . "\r\n"
            . "Connection: close\r\n\r\n"
            . $content;

        self::writeAll($socket, $data);
    }

    /**
     * Write all data to socket, handling partial writes.
     *
     * @param resource $socket
     */
    public static function writeAll($socket, string $data): void
    {
        $written = 0;
        $len = strlen($data);
        while ($written < $len) {
            $bytes = @fwrite($socket, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                throw new Exception('Socket write failed');
            }
            $written += $bytes;
        }
    }

    private function closeClient(int $id): void
    {
        if (isset($this->clients[$id])) {
            fclose($this->clients[$id]['socket']);
            unset($this->clients[$id]);
        }
    }

    public function afterRun(Closure $callback): void
    {
        $this->afterCallback = $callback;
    }

    public function ticker(Closure $callback): void
    {
        $this->tickerCallback = $callback;
    }

    public function router(Closure $callback): void
    {
        $this->routerCallback = $callback;
    }

    public function setAuthToken(string $token): void
    {
        $this->authToken = $token;
    }

    public function stop(): void
    {
        $this->stop = true;
    }
}
