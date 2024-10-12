<?php

namespace React\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Io\AbstractRequest;
use React\Http\Io\BufferedBody;
use React\Http\Io\ReadableBodyStream;
use React\Stream\ReadableStreamInterface;

/**
 * Respresents an outgoing HTTP request message.
 *
 * This class implements the
 * [PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
 * which extends the
 * [PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).
 *
 * This is mostly used internally to represent each outgoing HTTP request
 * message for the HTTP client implementation. Likewise, you can also use this
 * class with other HTTP client implementations and for tests.
 *
 * > Internally, this implementation builds on top of a base class which is
 *   considered an implementation detail that may change in the future.
 *
 * @see RequestInterface
 */
final class Request extends AbstractRequest implements RequestInterface
{
    /**
     * @param string                                         $method  HTTP method for the request.
     * @param string|UriInterface                            $url     URL for the request.
     * @param array<string,string|string[]>                  $headers Headers for the message.
     * @param string|ReadableStreamInterface|StreamInterface $body    Message body.
     * @param string                                         $version HTTP protocol version.
     * @throws \InvalidArgumentException for an invalid URL or body
     */
    public function __construct(
        $method,
        $url,
        array $headers = array(),
        $body = '',
        $version = '1.1'
    ) {
        if (\is_string($body)) {
            $body = new BufferedBody($body);
        } elseif ($body instanceof ReadableStreamInterface && !$body instanceof StreamInterface) {
            $body = new ReadableBodyStream($body);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Invalid request body given');
        }

        parent::__construct($method, $url, $headers, $body, $version);
    }
}
