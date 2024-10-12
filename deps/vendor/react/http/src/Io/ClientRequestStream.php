<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use React\Http\Message\Response;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/**
 * @event response
 * @event drain
 * @event error
 * @event close
 * @internal
 */
class ClientRequestStream extends EventEmitter implements WritableStreamInterface
{
    const STATE_INIT = 0;
    const STATE_WRITING_HEAD = 1;
    const STATE_HEAD_WRITTEN = 2;
    const STATE_END = 3;

    /** @var ClientConnectionManager */
    private $connectionManager;

    /** @var RequestInterface */
    private $request;

    /** @var ?ConnectionInterface */
    private $connection;

    /** @var string */
    private $buffer = '';

    private $responseFactory;
    private $state = self::STATE_INIT;
    private $ended = false;

    private $pendingWrites = '';

    public function __construct(ClientConnectionManager $connectionManager, RequestInterface $request)
    {
        $this->connectionManager = $connectionManager;
        $this->request = $request;
    }

    public function isWritable()
    {
        return self::STATE_END > $this->state && !$this->ended;
    }

    private function writeHead()
    {
        $this->state = self::STATE_WRITING_HEAD;

        $expected = 0;
        $headers = "{$this->request->getMethod()} {$this->request->getRequestTarget()} HTTP/{$this->request->getProtocolVersion()}\r\n";
        foreach ($this->request->getHeaders() as $name => $values) {
            if (\strpos($name, ':') !== false) {
                $expected = -1;
                break;
            }
            foreach ($values as $value) {
                $headers .= "$name: $value\r\n";
                ++$expected;
            }
        }

        /** @var array $m legacy PHP 5.3 only */
        if (!\preg_match('#^\S+ \S+ HTTP/1\.[01]\r\n#m', $headers) || \substr_count($headers, "\n") !== ($expected + 1) || (\PHP_VERSION_ID >= 50400 ? \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers) : \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers, $m)) !== $expected) {
            $this->closeError(new \InvalidArgumentException('Unable to send request with invalid request headers'));
            return;
        }

        $connectionRef = &$this->connection;
        $stateRef = &$this->state;
        $pendingWrites = &$this->pendingWrites;
        $that = $this;

        $promise = $this->connectionManager->connect($this->request->getUri());
        $promise->then(
            function (ConnectionInterface $connection) use ($headers, &$connectionRef, &$stateRef, &$pendingWrites, $that) {
                $connectionRef = $connection;
                assert($connectionRef instanceof ConnectionInterface);

                $connection->on('drain', array($that, 'handleDrain'));
                $connection->on('data', array($that, 'handleData'));
                $connection->on('end', array($that, 'handleEnd'));
                $connection->on('error', array($that, 'handleError'));
                $connection->on('close', array($that, 'close'));

                $more = $connection->write($headers . "\r\n" . $pendingWrites);

                assert($stateRef === ClientRequestStream::STATE_WRITING_HEAD);
                $stateRef = ClientRequestStream::STATE_HEAD_WRITTEN;

                // clear pending writes if non-empty
                if ($pendingWrites !== '') {
                    $pendingWrites = '';

                    if ($more) {
                        $that->emit('drain');
                    }
                }
            },
            array($this, 'closeError')
        );

        $this->on('close', function() use ($promise) {
            $promise->cancel();
        });
    }

    public function write($data)
    {
        if (!$this->isWritable()) {
            return false;
        }

        // write directly to connection stream if already available
        if (self::STATE_HEAD_WRITTEN <= $this->state) {
            return $this->connection->write($data);
        }

        // otherwise buffer and try to establish connection
        $this->pendingWrites .= $data;
        if (self::STATE_WRITING_HEAD > $this->state) {
            $this->writeHead();
        }

        return false;
    }

    public function end($data = null)
    {
        if (!$this->isWritable()) {
            return;
        }

        if (null !== $data) {
            $this->write($data);
        } else if (self::STATE_WRITING_HEAD > $this->state) {
            $this->writeHead();
        }

        $this->ended = true;
    }

    /** @internal */
    public function handleDrain()
    {
        $this->emit('drain');
    }

    /** @internal */
    public function handleData($data)
    {
        $this->buffer .= $data;

        // buffer until double CRLF (or double LF for compatibility with legacy servers)
        $eom = \strpos($this->buffer, "\r\n\r\n");
        $eomLegacy = \strpos($this->buffer, "\n\n");
        if ($eom !== false || $eomLegacy !== false) {
            try {
                if ($eom !== false && ($eomLegacy === false || $eom < $eomLegacy)) {
                    $response = Response::parseMessage(\substr($this->buffer, 0, $eom + 2));
                    $bodyChunk = (string) \substr($this->buffer, $eom + 4);
                } else {
                    $response = Response::parseMessage(\substr($this->buffer, 0, $eomLegacy + 1));
                    $bodyChunk = (string) \substr($this->buffer, $eomLegacy + 2);
                }
            } catch (\InvalidArgumentException $exception) {
                $this->closeError($exception);
                return;
            }

            // response headers successfully received => remove listeners for connection events
            $connection = $this->connection;
            assert($connection instanceof ConnectionInterface);
            $connection->removeListener('drain', array($this, 'handleDrain'));
            $connection->removeListener('data', array($this, 'handleData'));
            $connection->removeListener('end', array($this, 'handleEnd'));
            $connection->removeListener('error', array($this, 'handleError'));
            $connection->removeListener('close', array($this, 'close'));
            $this->connection = null;
            $this->buffer = '';

            // take control over connection handling and check if we can reuse the connection once response body closes
            $that = $this;
            $request = $this->request;
            $connectionManager = $this->connectionManager;
            $successfulEndReceived = false;
            $input = $body = new CloseProtectionStream($connection);
            $input->on('close', function () use ($connection, $that, $connectionManager, $request, $response, &$successfulEndReceived) {
                // only reuse connection after successful response and both request and response allow keep alive
                if ($successfulEndReceived && $connection->isReadable() && $that->hasMessageKeepAliveEnabled($response) && $that->hasMessageKeepAliveEnabled($request)) {
                    $connectionManager->keepAlive($request->getUri(), $connection);
                } else {
                    $connection->close();
                }

                $that->close();
            });

            // determine length of response body
            $length = null;
            $code = $response->getStatusCode();
            if ($this->request->getMethod() === 'HEAD' || ($code >= 100 && $code < 200) || $code == Response::STATUS_NO_CONTENT || $code == Response::STATUS_NOT_MODIFIED) {
                $length = 0;
            } elseif (\strtolower($response->getHeaderLine('Transfer-Encoding')) === 'chunked') {
                $body = new ChunkedDecoder($body);
            } elseif ($response->hasHeader('Content-Length')) {
                $length = (int) $response->getHeaderLine('Content-Length');
            }
            $response = $response->withBody($body = new ReadableBodyStream($body, $length));
            $body->on('end', function () use (&$successfulEndReceived) {
                $successfulEndReceived = true;
            });

            // emit response with streaming response body (see `Sender`)
            $this->emit('response', array($response, $body));

            // re-emit HTTP response body to trigger body parsing if parts of it are buffered
            if ($bodyChunk !== '') {
                $input->handleData($bodyChunk);
            } elseif ($length === 0) {
                $input->handleEnd();
            }
        }
    }

    /** @internal */
    public function handleEnd()
    {
        $this->closeError(new \RuntimeException(
            "Connection ended before receiving response"
        ));
    }

    /** @internal */
    public function handleError(\Exception $error)
    {
        $this->closeError(new \RuntimeException(
            "An error occurred in the underlying stream",
            0,
            $error
        ));
    }

    /** @internal */
    public function closeError(\Exception $error)
    {
        if (self::STATE_END <= $this->state) {
            return;
        }
        $this->emit('error', array($error));
        $this->close();
    }

    public function close()
    {
        if (self::STATE_END <= $this->state) {
            return;
        }

        $this->state = self::STATE_END;
        $this->pendingWrites = '';
        $this->buffer = '';

        if ($this->connection instanceof ConnectionInterface) {
            $this->connection->close();
            $this->connection = null;
        }

        $this->emit('close');
        $this->removeAllListeners();
    }

    /**
     * @internal
     * @return bool
     * @link https://www.rfc-editor.org/rfc/rfc9112#section-9.3
     * @link https://www.rfc-editor.org/rfc/rfc7230#section-6.1
     */
    public function hasMessageKeepAliveEnabled(MessageInterface $message)
    {
        // @link https://www.rfc-editor.org/rfc/rfc9110#section-7.6.1
        $connectionOptions = \array_map('trim', \explode(',', \strtolower($message->getHeaderLine('Connection'))));

        if (\in_array('close', $connectionOptions, true)) {
            return false;
        }

        if ($message->getProtocolVersion() === '1.1') {
            return true;
        }

        if (\in_array('keep-alive', $connectionOptions, true)) {
            return true;
        }

        return false;
    }
}
