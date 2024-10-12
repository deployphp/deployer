<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Socket\ConnectionInterface;
use Exception;

/**
 * [Internal] Parses an incoming request header from an input stream
 *
 * This is used internally to parse the request header from the connection and
 * then process the remaining connection as the request body.
 *
 * @event headers
 * @event error
 *
 * @internal
 */
class RequestHeaderParser extends EventEmitter
{
    private $maxSize = 8192;

    /** @var Clock */
    private $clock;

    /** @var array<string|int,array<string,string>> */
    private $connectionParams = array();

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function handle(ConnectionInterface $conn)
    {
        $buffer = '';
        $maxSize = $this->maxSize;
        $that = $this;
        $conn->on('data', $fn = function ($data) use (&$buffer, &$fn, $conn, $maxSize, $that) {
            // append chunk of data to buffer and look for end of request headers
            $buffer .= $data;
            $endOfHeader = \strpos($buffer, "\r\n\r\n");

            // reject request if buffer size is exceeded
            if ($endOfHeader > $maxSize || ($endOfHeader === false && isset($buffer[$maxSize]))) {
                $conn->removeListener('data', $fn);
                $fn = null;

                $that->emit('error', array(
                    new \OverflowException("Maximum header size of {$maxSize} exceeded.", Response::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE),
                    $conn
                ));
                return;
            }

            // ignore incomplete requests
            if ($endOfHeader === false) {
                return;
            }

            // request headers received => try to parse request
            $conn->removeListener('data', $fn);
            $fn = null;

            try {
                $request = $that->parseRequest(
                    (string)\substr($buffer, 0, $endOfHeader + 2),
                    $conn
                );
            } catch (Exception $exception) {
                $buffer = '';
                $that->emit('error', array(
                    $exception,
                    $conn
                ));
                return;
            }

            $contentLength = 0;
            if ($request->hasHeader('Transfer-Encoding')) {
                $contentLength = null;
            } elseif ($request->hasHeader('Content-Length')) {
                $contentLength = (int)$request->getHeaderLine('Content-Length');
            }

            if ($contentLength === 0) {
                // happy path: request body is known to be empty
                $stream = new EmptyBodyStream();
                $request = $request->withBody($stream);
            } else {
                // otherwise body is present => delimit using Content-Length or ChunkedDecoder
                $stream = new CloseProtectionStream($conn);
                if ($contentLength !== null) {
                    $stream = new LengthLimitedStream($stream, $contentLength);
                } else {
                    $stream = new ChunkedDecoder($stream);
                }

                $request = $request->withBody(new HttpBodyStream($stream, $contentLength));
            }

            $bodyBuffer = isset($buffer[$endOfHeader + 4]) ? \substr($buffer, $endOfHeader + 4) : '';
            $buffer = '';
            $that->emit('headers', array($request, $conn));

            if ($bodyBuffer !== '') {
                $conn->emit('data', array($bodyBuffer));
            }

            // happy path: request body is known to be empty => immediately end stream
            if ($contentLength === 0) {
                $stream->emit('end');
                $stream->close();
            }
        });
    }

    /**
     * @param string $headers buffer string containing request headers only
     * @param ConnectionInterface $connection
     * @return ServerRequestInterface
     * @throws \InvalidArgumentException
     * @internal
     */
    public function parseRequest($headers, ConnectionInterface $connection)
    {
        // reuse same connection params for all server params for this connection
        $cid = \PHP_VERSION_ID < 70200 ? \spl_object_hash($connection) : \spl_object_id($connection);
        if (isset($this->connectionParams[$cid])) {
            $serverParams = $this->connectionParams[$cid];
        } else {
            // assign new server params for new connection
            $serverParams = array();

            // scheme is `http` unless TLS is used
            $localSocketUri = $connection->getLocalAddress();
            $localParts = $localSocketUri === null ? array() : \parse_url($localSocketUri);
            if (isset($localParts['scheme']) && $localParts['scheme'] === 'tls') {
                $serverParams['HTTPS'] = 'on';
            }

            // apply SERVER_ADDR and SERVER_PORT if server address is known
            // address should always be known, even for Unix domain sockets (UDS)
            // but skip UDS as it doesn't have a concept of host/port.
            if ($localSocketUri !== null && isset($localParts['host'], $localParts['port'])) {
                $serverParams['SERVER_ADDR'] = $localParts['host'];
                $serverParams['SERVER_PORT'] = $localParts['port'];
            }

            // apply REMOTE_ADDR and REMOTE_PORT if source address is known
            // address should always be known, unless this is over Unix domain sockets (UDS)
            $remoteSocketUri = $connection->getRemoteAddress();
            if ($remoteSocketUri !== null) {
                $remoteAddress = \parse_url($remoteSocketUri);
                $serverParams['REMOTE_ADDR'] = $remoteAddress['host'];
                $serverParams['REMOTE_PORT'] = $remoteAddress['port'];
            }

            // remember server params for all requests from this connection, reset on connection close
            $this->connectionParams[$cid] = $serverParams;
            $params =& $this->connectionParams;
            $connection->on('close', function () use (&$params, $cid) {
                assert(\is_array($params));
                unset($params[$cid]);
            });
        }

        // create new obj implementing ServerRequestInterface by preserving all
        // previous properties and restoring original request-target
        $serverParams['REQUEST_TIME'] = (int) ($now = $this->clock->now());
        $serverParams['REQUEST_TIME_FLOAT'] = $now;

        return ServerRequest::parseMessage($headers, $serverParams);
    }
}
