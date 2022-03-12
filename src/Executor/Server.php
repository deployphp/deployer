<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Task\Context;
use Psr\Http\Message\ServerRequestInterface;
use React;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Server
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var React\EventLoop\LoopInterface
     */
    public $loop;

    /**
     * @var int
     */
    private $port;

    public function __construct(
        OutputInterface $output,
        Deployer        $deployer
    )
    {
        $this->output = $output;
        $this->deployer = $deployer;
    }

    public function start()
    {
        $this->loop = Loop::get();
        $server = new HttpServer(
            $this->loop,
            new React\Http\Middleware\StreamingRequestMiddleware(),
            new React\Http\Middleware\RequestBodyBufferMiddleware(16 * 1024 * 1024), // 16 MiB
            function (ServerRequestInterface $request) {
                try {
                    return $this->router($request);
                } catch (Throwable $exception) {
                    Deployer::printException($this->output, $exception);
                    return new React\Http\Message\Response(500, ['Content-Type' => 'text/plain'], 'Master error: ' . $exception->getMessage());
                }
            }
        );
        $socket = new React\Socket\Server(0, $this->loop);
        $server->listen($socket);
        $address = $socket->getAddress();
        $this->port = parse_url($address, PHP_URL_PORT);
    }

    private function router(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath();
        switch ($path) {
            case '/load':
                ['host' => $host] = json_decode((string)$request->getBody(), true);

                $host = $this->deployer->hosts->get($host);
                $config = json_encode($host->config()->persist());

                return new Response(200, ['Content-Type' => 'application/json'], $config);

            case '/save':
                ['host' => $host, 'config' => $config] = json_decode((string)$request->getBody(), true);

                $host = $this->deployer->hosts->get($host);
                $host->config()->update($config);

                return new Response(200, ['Content-Type' => 'application/json'], 'true');

            case '/proxy':
                ['host' => $host, 'func' => $func, 'arguments' => $arguments] = json_decode((string)$request->getBody(), true);

                Context::push(new Context($this->deployer->hosts->get($host)));
                $answer = call_user_func($func, ...$arguments);
                Context::pop();

                return new Response(200, ['Content-Type' => 'application/json'], json_encode($answer));

            default:
                throw new Exception('Server path not found: ' . $request->getUri()->getPath());
        }
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
