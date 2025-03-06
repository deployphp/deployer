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
     * @var resource[]
     */
    private array $clientSockets = [];

    private Closure $afterCallback;
    private Closure $tickerCallback;
    private Closure $routerCallback;

    public function __construct($host, $port, OutputInterface $output)
    {
        self::checkRequiredExtensionsExists();
        $this->host = $host;
        $this->port = $port;
        $this->output = $output;
    }

    public static function checkRequiredExtensionsExists(): void
    {
        if (!function_exists('socket_import_stream')) {
            throw new Exception('Required PHP extension "sockets" is not loaded');
        }
        if (!function_exists('stream_set_blocking')) {
            throw new Exception('Required PHP extension "stream" is not loaded');
        }
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
            list(, $port) = explode(':', $name);
            $this->port = (int) $port;
        } else {
            throw new Exception("Failed to get the assigned port");
        }
    }

    private function acceptNewConnections(): void
    {
        $newClientSocket = @stream_socket_accept($this->socket, 0);
        if ($newClientSocket) {
            if (!stream_set_blocking($newClientSocket, false)) {
                throw new Exception("Failed to set client socket to non-blocking mode");
            }
            $this->clientSockets[] = $newClientSocket;
        }
    }

    private function handleClientRequests(): void
    {
        foreach ($this->clientSockets as $key => $clientSocket) {
            if (feof($clientSocket)) {
                $this->closeClientSocket($clientSocket, $key);
                continue;
            }

            $request = $this->readClientRequest($clientSocket);
            list($path, $payload) = $this->parseRequest($request);
            $response = ($this->routerCallback)($path, $payload);

            $this->sendResponse($clientSocket, $response);
            $this->closeClientSocket($clientSocket, $key);
        }
    }

    private function readClientRequest($clientSocket)
    {
        $request = '';
        while (($chunk = @fread($clientSocket, 16384)) !== false) {
            $request .= $chunk;
            if (strpos($request, "\r\n\r\n") !== false) {
                break;
            }
        }

        if ($chunk === false && !feof($clientSocket)) {
            throw new Exception("Socket read failed");
        }

        return $request;
    }

    private function parseRequest($request)
    {
        $lines = explode("\r\n", $request);
        $requestLine = $lines[0];
        $parts = explode(' ', $requestLine);
        if (count($parts) !== 3) {
            throw new Exception("Malformed request line: $requestLine");
        }
        $path = $parts[1];

        $headers = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (empty($line)) {
                break;
            }
            [$key, $value] = explode(':', $line, 2);
            $headers[$key] = trim($value);
        }
        if (empty($headers['Content-Type']) || $headers['Content-Type'] !== 'application/json') {
            throw new Exception("Malformed request: invalid Content-Type");
        }

        $payload = json_decode(implode("\n", array_slice($lines, $i + 1)), true, flags: JSON_THROW_ON_ERROR);
        return [$path, $payload];
    }

    private function sendResponse($clientSocket, Response $response)
    {
        $code = $response->getStatus();
        $content = json_encode($response->getBody(), flags: JSON_PRETTY_PRINT);
        $headers = "HTTP/1.1 $code OK\r\n" .
            "Content-Type: application/json\r\n" .
            "Content-Length: " . strlen($content) . "\r\n" .
            "Connection: close\r\n\r\n";
        fwrite($clientSocket, $headers . $content);
    }

    private function closeClientSocket($clientSocket, $key): void
    {
        fclose($clientSocket);
        unset($this->clientSockets[$key]);
    }

    public function afterRun(Closure $param): void
    {
        $this->afterCallback = $param;
    }

    public function ticker(Closure $param): void
    {
        $this->tickerCallback = $param;
    }

    public function router(Closure $param)
    {
        $this->routerCallback = $param;
    }

    public function stop(): void
    {
        $this->stop = true;
    }
}
