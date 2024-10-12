<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * The internal `StreamingServer` class is responsible for handling incoming connections and then
 * processing each incoming HTTP request.
 *
 * Unlike the [`HttpServer`](#httpserver) class, it does not buffer and parse the incoming
 * HTTP request body by default. This means that the request handler will be
 * invoked with a streaming request body. Once the request headers have been
 * received, it will invoke the request handler function. This request handler
 * function needs to be passed to the constructor and will be invoked with the
 * respective [request](#request) object and expects a [response](#response)
 * object in return:
 *
 * ```php
 * $server = new StreamingServer($loop, function (ServerRequestInterface $request) {
 *     return new Response(
 *         Response::STATUS_OK,
 *         array(
 *             'Content-Type' => 'text/plain'
 *         ),
 *         "Hello World!\n"
 *     );
 * });
 * ```
 *
 * Each incoming HTTP request message is always represented by the
 * [PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface),
 * see also following [request](#request) chapter for more details.
 * Each outgoing HTTP response message is always represented by the
 * [PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface),
 * see also following [response](#response) chapter for more details.
 *
 * In order to process any connections, the server needs to be attached to an
 * instance of `React\Socket\ServerInterface` through the [`listen()`](#listen) method
 * as described in the following chapter. In its most simple form, you can attach
 * this to a [`React\Socket\SocketServer`](https://github.com/reactphp/socket#socketserver)
 * in order to start a plaintext HTTP server like this:
 *
 * ```php
 * $server = new StreamingServer($loop, $handler);
 *
 * $socket = new React\Socket\SocketServer('0.0.0.0:8080', array(), $loop);
 * $server->listen($socket);
 * ```
 *
 * See also the [`listen()`](#listen) method and the [first example](examples) for more details.
 *
 * The `StreamingServer` class is considered advanced usage and unless you know
 * what you're doing, you're recommended to use the [`HttpServer`](#httpserver) class
 * instead. The `StreamingServer` class is specifically designed to help with
 * more advanced use cases where you want to have full control over consuming
 * the incoming HTTP request body and concurrency settings.
 *
 * In particular, this class does not buffer and parse the incoming HTTP request
 * in memory. It will invoke the request handler function once the HTTP request
 * headers have been received, i.e. before receiving the potentially much larger
 * HTTP request body. This means the [request](#request) passed to your request
 * handler function may not be fully compatible with PSR-7. See also
 * [streaming request](#streaming-request) below for more details.
 *
 * @see \React\Http\HttpServer
 * @see \React\Http\Message\Response
 * @see self::listen()
 * @internal
 */
final class StreamingServer extends EventEmitter
{
    private $callback;
    private $parser;

    /** @var Clock */
    private $clock;

    /**
     * Creates an HTTP server that invokes the given callback for each incoming HTTP request
     *
     * In order to process any connections, the server needs to be attached to an
     * instance of `React\Socket\ServerInterface` which emits underlying streaming
     * connections in order to then parse incoming data as HTTP.
     * See also [listen()](#listen) for more details.
     *
     * @param LoopInterface $loop
     * @param callable $requestHandler
     * @see self::listen()
     */
    public function __construct(LoopInterface $loop, $requestHandler)
    {
        if (!\is_callable($requestHandler)) {
            throw new \InvalidArgumentException('Invalid request handler given');
        }

        $this->callback = $requestHandler;
        $this->clock = new Clock($loop);
        $this->parser = new RequestHeaderParser($this->clock);

        $that = $this;
        $this->parser->on('headers', function (ServerRequestInterface $request, ConnectionInterface $conn) use ($that) {
            $that->handleRequest($conn, $request);
        });

        $this->parser->on('error', function(\Exception $e, ConnectionInterface $conn) use ($that) {
            $that->emit('error', array($e));

            // parsing failed => assume dummy request and send appropriate error
            $that->writeError(
                $conn,
                $e->getCode() !== 0 ? $e->getCode() : Response::STATUS_BAD_REQUEST,
                new ServerRequest('GET', '/')
            );
        });
    }

    /**
     * Starts listening for HTTP requests on the given socket server instance
     *
     * @param ServerInterface $socket
     * @see \React\Http\HttpServer::listen()
     */
    public function listen(ServerInterface $socket)
    {
        $socket->on('connection', array($this->parser, 'handle'));
    }

    /** @internal */
    public function handleRequest(ConnectionInterface $conn, ServerRequestInterface $request)
    {
        if ($request->getProtocolVersion() !== '1.0' && '100-continue' === \strtolower($request->getHeaderLine('Expect'))) {
            $conn->write("HTTP/1.1 100 Continue\r\n\r\n");
        }

        // execute request handler callback
        $callback = $this->callback;
        try {
            $response = $callback($request);
        } catch (\Exception $error) {
            // request handler callback throws an Exception
            $response = Promise\reject($error);
        } catch (\Throwable $error) { // @codeCoverageIgnoreStart
            // request handler callback throws a PHP7+ Error
            $response = Promise\reject($error); // @codeCoverageIgnoreEnd
        }

        // cancel pending promise once connection closes
        $connectionOnCloseResponseCancelerHandler = function () {};
        if ($response instanceof PromiseInterface && \method_exists($response, 'cancel')) {
            $connectionOnCloseResponseCanceler = function () use ($response) {
                $response->cancel();
            };
            $connectionOnCloseResponseCancelerHandler = function () use ($connectionOnCloseResponseCanceler, $conn) {
                if ($connectionOnCloseResponseCanceler !== null) {
                    $conn->removeListener('close', $connectionOnCloseResponseCanceler);
                }
            };
            $conn->on('close', $connectionOnCloseResponseCanceler);
        }

        // happy path: response returned, handle and return immediately
        if ($response instanceof ResponseInterface) {
            return $this->handleResponse($conn, $request, $response);
        }

        // did not return a promise? this is an error, convert into one for rejection below.
        if (!$response instanceof PromiseInterface) {
            $response = Promise\resolve($response);
        }

        $that = $this;
        $response->then(
            function ($response) use ($that, $conn, $request) {
                if (!$response instanceof ResponseInterface) {
                    $message = 'The response callback is expected to resolve with an object implementing Psr\Http\Message\ResponseInterface, but resolved with "%s" instead.';
                    $message = \sprintf($message, \is_object($response) ? \get_class($response) : \gettype($response));
                    $exception = new \RuntimeException($message);

                    $that->emit('error', array($exception));
                    return $that->writeError($conn, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
                }
                $that->handleResponse($conn, $request, $response);
            },
            function ($error) use ($that, $conn, $request) {
                $message = 'The response callback is expected to resolve with an object implementing Psr\Http\Message\ResponseInterface, but rejected with "%s" instead.';
                $message = \sprintf($message, \is_object($error) ? \get_class($error) : \gettype($error));

                $previous = null;

                if ($error instanceof \Throwable || $error instanceof \Exception) {
                    $previous = $error;
                }

                $exception = new \RuntimeException($message, 0, $previous);

                $that->emit('error', array($exception));
                return $that->writeError($conn, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
            }
        )->then($connectionOnCloseResponseCancelerHandler, $connectionOnCloseResponseCancelerHandler);
    }

    /** @internal */
    public function writeError(ConnectionInterface $conn, $code, ServerRequestInterface $request)
    {
        $response = new Response(
            $code,
            array(
                'Content-Type' => 'text/plain',
                'Connection' => 'close' // we do not want to keep the connection open after an error
            ),
            'Error ' . $code
        );

        // append reason phrase to response body if known
        $reason = $response->getReasonPhrase();
        if ($reason !== '') {
            $body = $response->getBody();
            $body->seek(0, SEEK_END);
            $body->write(': ' . $reason);
        }

        $this->handleResponse($conn, $request, $response);
    }


    /** @internal */
    public function handleResponse(ConnectionInterface $connection, ServerRequestInterface $request, ResponseInterface $response)
    {
        // return early and close response body if connection is already closed
        $body = $response->getBody();
        if (!$connection->isWritable()) {
            $body->close();
            return;
        }

        $code = $response->getStatusCode();
        $method = $request->getMethod();

        // assign HTTP protocol version from request automatically
        $version = $request->getProtocolVersion();
        $response = $response->withProtocolVersion($version);

        // assign default "Server" header automatically
        if (!$response->hasHeader('Server')) {
            $response = $response->withHeader('Server', 'ReactPHP/1');
        } elseif ($response->getHeaderLine('Server') === ''){
            $response = $response->withoutHeader('Server');
        }

        // assign default "Date" header from current time automatically
        if (!$response->hasHeader('Date')) {
            // IMF-fixdate  = day-name "," SP date1 SP time-of-day SP GMT
            $response = $response->withHeader('Date', gmdate('D, d M Y H:i:s', (int) $this->clock->now()) . ' GMT');
        } elseif ($response->getHeaderLine('Date') === ''){
            $response = $response->withoutHeader('Date');
        }

        // assign "Content-Length" header automatically
        $chunked = false;
        if (($method === 'CONNECT' && $code >= 200 && $code < 300) || ($code >= 100 && $code < 200) || $code === Response::STATUS_NO_CONTENT) {
            // 2xx response to CONNECT and 1xx and 204 MUST NOT include Content-Length or Transfer-Encoding header
            $response = $response->withoutHeader('Content-Length');
        } elseif ($method === 'HEAD' && $response->hasHeader('Content-Length')) {
            // HEAD Request: preserve explicit Content-Length
        } elseif ($code === Response::STATUS_NOT_MODIFIED && ($response->hasHeader('Content-Length') || $body->getSize() === 0)) {
            // 304 Not Modified: preserve explicit Content-Length and preserve missing header if body is empty
        } elseif ($body->getSize() !== null) {
            // assign Content-Length header when using a "normal" buffered body string
            $response = $response->withHeader('Content-Length', (string)$body->getSize());
        } elseif (!$response->hasHeader('Content-Length') && $version === '1.1') {
            // assign chunked transfer-encoding if no 'content-length' is given for HTTP/1.1 responses
            $chunked = true;
        }

        // assign "Transfer-Encoding" header automatically
        if ($chunked) {
            $response = $response->withHeader('Transfer-Encoding', 'chunked');
        } else {
            // remove any Transfer-Encoding headers unless automatically enabled above
            $response = $response->withoutHeader('Transfer-Encoding');
        }

        // assign "Connection" header automatically
        $persist = false;
        if ($code === Response::STATUS_SWITCHING_PROTOCOLS) {
            // 101 (Switching Protocols) response uses Connection: upgrade header
            // This implies that this stream now uses another protocol and we
            // may not persist this connection for additional requests.
            $response = $response->withHeader('Connection', 'upgrade');
        } elseif (\strtolower($request->getHeaderLine('Connection')) === 'close' || \strtolower($response->getHeaderLine('Connection')) === 'close') {
            // obey explicit "Connection: close" request header or response header if present
            $response = $response->withHeader('Connection', 'close');
        } elseif ($version === '1.1') {
            // HTTP/1.1 assumes persistent connection support by default, so we don't need to inform client
            $persist = true;
        } elseif (strtolower($request->getHeaderLine('Connection')) === 'keep-alive') {
            // obey explicit "Connection: keep-alive" request header and inform client
            $persist = true;
            $response = $response->withHeader('Connection', 'keep-alive');
        } else {
            // remove any Connection headers unless automatically enabled above
            $response = $response->withoutHeader('Connection');
        }

        // 101 (Switching Protocols) response (for Upgrade request) forwards upgraded data through duplex stream
        // 2xx (Successful) response to CONNECT forwards tunneled application data through duplex stream
        if (($code === Response::STATUS_SWITCHING_PROTOCOLS || ($method === 'CONNECT' && $code >= 200 && $code < 300)) && $body instanceof HttpBodyStream && $body->input instanceof WritableStreamInterface) {
            if ($request->getBody()->isReadable()) {
                // request is still streaming => wait for request close before forwarding following data from connection
                $request->getBody()->on('close', function () use ($connection, $body) {
                    if ($body->input->isWritable()) {
                        $connection->pipe($body->input);
                        $connection->resume();
                    }
                });
            } elseif ($body->input->isWritable()) {
                // request already closed => forward following data from connection
                $connection->pipe($body->input);
                $connection->resume();
            }
        }

        // build HTTP response header by appending status line and header fields
        $expected = 0;
        $headers = "HTTP/" . $version . " " . $code . " " . $response->getReasonPhrase() . "\r\n";
        foreach ($response->getHeaders() as $name => $values) {
            if (\strpos($name, ':') !== false) {
                $expected = -1;
                break;
            }
            foreach ($values as $value) {
                $headers .= $name . ": " . $value . "\r\n";
                ++$expected;
            }
        }

        /** @var array $m legacy PHP 5.3 only */
        if ($code < 100 || $code > 999 || \substr_count($headers, "\n") !== ($expected + 1) || (\PHP_VERSION_ID >= 50400 ? \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers) : \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers, $m)) !== $expected) {
            $this->emit('error', array(new \InvalidArgumentException('Unable to send response with invalid response headers')));
            $this->writeError($connection, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
            return;
        }

        // response to HEAD and 1xx, 204 and 304 responses MUST NOT include a body
        // exclude status 101 (Switching Protocols) here for Upgrade request handling above
        if ($method === 'HEAD' || ($code >= 100 && $code < 200 && $code !== Response::STATUS_SWITCHING_PROTOCOLS) || $code === Response::STATUS_NO_CONTENT || $code === Response::STATUS_NOT_MODIFIED) {
            $body->close();
            $body = '';
        }

        // this is a non-streaming response body or the body stream already closed?
        if (!$body instanceof ReadableStreamInterface || !$body->isReadable()) {
            // add final chunk if a streaming body is already closed and uses `Transfer-Encoding: chunked`
            if ($body instanceof ReadableStreamInterface && $chunked) {
                $body = "0\r\n\r\n";
            }

            // write response headers and body
            $connection->write($headers . "\r\n" . $body);

            // either wait for next request over persistent connection or end connection
            if ($persist) {
                $this->parser->handle($connection);
            } else {
                $connection->end();
            }
            return;
        }

        $connection->write($headers . "\r\n");

        if ($chunked) {
            $body = new ChunkedEncoder($body);
        }

        // Close response stream once connection closes.
        // Note that this TCP/IP close detection may take some time,
        // in particular this may only fire on a later read/write attempt.
        $connection->on('close', array($body, 'close'));

        // write streaming body and then wait for next request over persistent connection
        if ($persist) {
            $body->pipe($connection, array('end' => false));
            $parser = $this->parser;
            $body->on('end', function () use ($connection, $parser, $body) {
                $connection->removeListener('close', array($body, 'close'));
                $parser->handle($connection);
            });
        } else {
            $body->pipe($connection);
        }
    }
}
