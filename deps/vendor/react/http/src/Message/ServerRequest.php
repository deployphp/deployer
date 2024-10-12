<?php

namespace React\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Io\AbstractRequest;
use React\Http\Io\BufferedBody;
use React\Http\Io\HttpBodyStream;
use React\Stream\ReadableStreamInterface;

/**
 * Respresents an incoming server request message.
 *
 * This class implements the
 * [PSR-7 `ServerRequestInterface`](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface)
 * which extends the
 * [PSR-7 `RequestInterface`](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
 * which in turn extends the
 * [PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).
 *
 * This is mostly used internally to represent each incoming request message.
 * Likewise, you can also use this class in test cases to test how your web
 * application reacts to certain HTTP requests.
 *
 * > Internally, this implementation builds on top of a base class which is
 *   considered an implementation detail that may change in the future.
 *
 * @see ServerRequestInterface
 */
final class ServerRequest extends AbstractRequest implements ServerRequestInterface
{
    private $attributes = array();

    private $serverParams;
    private $fileParams = array();
    private $cookies = array();
    private $queryParams = array();
    private $parsedBody;

    /**
     * @param string                                         $method       HTTP method for the request.
     * @param string|UriInterface                            $url          URL for the request.
     * @param array<string,string|string[]>                  $headers      Headers for the message.
     * @param string|ReadableStreamInterface|StreamInterface $body         Message body.
     * @param string                                         $version      HTTP protocol version.
     * @param array<string,string>                           $serverParams server-side parameters
     * @throws \InvalidArgumentException for an invalid URL or body
     */
    public function __construct(
        $method,
        $url,
        array $headers = array(),
        $body = '',
        $version = '1.1',
        $serverParams = array()
    ) {
        if (\is_string($body)) {
            $body = new BufferedBody($body);
        } elseif ($body instanceof ReadableStreamInterface && !$body instanceof StreamInterface) {
            $temp = new self($method, '', $headers);
            $size = (int) $temp->getHeaderLine('Content-Length');
            if (\strtolower($temp->getHeaderLine('Transfer-Encoding')) === 'chunked') {
                $size = null;
            }
            $body = new HttpBodyStream($body, $size);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Invalid server request body given');
        }

        parent::__construct($method, $url, $headers, $body, $version);

        $this->serverParams = $serverParams;

        $query = $this->getUri()->getQuery();
        if ($query !== '') {
            \parse_str($query, $this->queryParams);
        }

        // Multiple cookie headers are not allowed according
        // to https://tools.ietf.org/html/rfc6265#section-5.4
        $cookieHeaders = $this->getHeader("Cookie");

        if (count($cookieHeaders) === 1) {
            $this->cookies = $this->parseCookie($cookieHeaders[0]);
        }
    }

    public function getServerParams()
    {
        return $this->serverParams;
    }

    public function getCookieParams()
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookies = $cookies;
        return $new;
    }

    public function getQueryParams()
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    public function getUploadedFiles()
    {
        return $this->fileParams;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->fileParams = $uploadedFiles;
        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $default;
        }
        return $this->attributes[$name];
    }

    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    public function withoutAttribute($name)
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    /**
     * @param string $cookie
     * @return array
     */
    private function parseCookie($cookie)
    {
        $cookieArray = \explode(';', $cookie);
        $result = array();

        foreach ($cookieArray as $pair) {
            $pair = \trim($pair);
            $nameValuePair = \explode('=', $pair, 2);

            if (\count($nameValuePair) === 2) {
                $key = $nameValuePair[0];
                $value = \urldecode($nameValuePair[1]);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * [Internal] Parse incoming HTTP protocol message
     *
     * @internal
     * @param string $message
     * @param array<string,string|int|float> $serverParams
     * @return self
     * @throws \InvalidArgumentException if given $message is not a valid HTTP request message
     */
    public static function parseMessage($message, array $serverParams)
    {
        // parse request line like "GET /path HTTP/1.1"
        $start = array();
        if (!\preg_match('#^(?<method>[^ ]+) (?<target>[^ ]+) HTTP/(?<version>\d\.\d)#m', $message, $start)) {
            throw new \InvalidArgumentException('Unable to parse invalid request-line');
        }

        // only support HTTP/1.1 and HTTP/1.0 requests
        if ($start['version'] !== '1.1' && $start['version'] !== '1.0') {
            throw new \InvalidArgumentException('Received request with invalid protocol version', Response::STATUS_VERSION_NOT_SUPPORTED);
        }

        // check number of valid header fields matches number of lines + request line
        $matches = array();
        $n = \preg_match_all(self::REGEX_HEADERS, $message, $matches, \PREG_SET_ORDER);
        if (\substr_count($message, "\n") !== $n + 1) {
            throw new \InvalidArgumentException('Unable to parse invalid request header fields');
        }

        // format all header fields into associative array
        $host = null;
        $headers = array();
        foreach ($matches as $match) {
            $headers[$match[1]][] = $match[2];

            // match `Host` request header
            if ($host === null && \strtolower($match[1]) === 'host') {
                $host = $match[2];
            }
        }

        // scheme is `http` unless TLS is used
        $scheme = isset($serverParams['HTTPS']) ? 'https://' : 'http://';

        // default host if unset comes from local socket address or defaults to localhost
        $hasHost = $host !== null;
        if ($host === null) {
            $host = isset($serverParams['SERVER_ADDR'], $serverParams['SERVER_PORT']) ? $serverParams['SERVER_ADDR'] . ':' . $serverParams['SERVER_PORT'] : '127.0.0.1';
        }

        if ($start['method'] === 'OPTIONS' && $start['target'] === '*') {
            // support asterisk-form for `OPTIONS *` request line only
            $uri = $scheme . $host;
        } elseif ($start['method'] === 'CONNECT') {
            $parts = \parse_url('tcp://' . $start['target']);

            // check this is a valid authority-form request-target (host:port)
            if (!isset($parts['scheme'], $parts['host'], $parts['port']) || \count($parts) !== 3) {
                throw new \InvalidArgumentException('CONNECT method MUST use authority-form request target');
            }
            $uri = $scheme . $start['target'];
        } else {
            // support absolute-form or origin-form for proxy requests
            if ($start['target'][0] === '/') {
                $uri = $scheme . $host . $start['target'];
            } else {
                // ensure absolute-form request-target contains a valid URI
                $parts = \parse_url($start['target']);

                // make sure value contains valid host component (IP or hostname), but no fragment
                if (!isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'http' || isset($parts['fragment'])) {
                    throw new \InvalidArgumentException('Invalid absolute-form request-target');
                }

                $uri = $start['target'];
            }
        }

        $request = new self(
            $start['method'],
            $uri,
            $headers,
            '',
            $start['version'],
            $serverParams
        );

        // only assign request target if it is not in origin-form (happy path for most normal requests)
        if ($start['target'][0] !== '/') {
            $request = $request->withRequestTarget($start['target']);
        }

        if ($hasHost) {
            // Optional Host request header value MUST be valid (host and optional port)
            $parts = \parse_url('http://' . $request->getHeaderLine('Host'));

            // make sure value contains valid host component (IP or hostname)
            if (!$parts || !isset($parts['scheme'], $parts['host'])) {
                $parts = false;
            }

            // make sure value does not contain any other URI component
            if (\is_array($parts)) {
                unset($parts['scheme'], $parts['host'], $parts['port']);
            }
            if ($parts === false || $parts) {
                throw new \InvalidArgumentException('Invalid Host header value');
            }
        } elseif (!$hasHost && $start['version'] === '1.1' && $start['method'] !== 'CONNECT') {
            // require Host request header for HTTP/1.1 (except for CONNECT method)
            throw new \InvalidArgumentException('Missing required Host request header');
        } elseif (!$hasHost) {
            // remove default Host request header for HTTP/1.0 when not explicitly given
            $request = $request->withoutHeader('Host');
        }

        // ensure message boundaries are valid according to Content-Length and Transfer-Encoding request headers
        if ($request->hasHeader('Transfer-Encoding')) {
            if (\strtolower($request->getHeaderLine('Transfer-Encoding')) !== 'chunked') {
                throw new \InvalidArgumentException('Only chunked-encoding is allowed for Transfer-Encoding', Response::STATUS_NOT_IMPLEMENTED);
            }

            // Transfer-Encoding: chunked and Content-Length header MUST NOT be used at the same time
            // as per https://tools.ietf.org/html/rfc7230#section-3.3.3
            if ($request->hasHeader('Content-Length')) {
                throw new \InvalidArgumentException('Using both `Transfer-Encoding: chunked` and `Content-Length` is not allowed', Response::STATUS_BAD_REQUEST);
            }
        } elseif ($request->hasHeader('Content-Length')) {
            $string = $request->getHeaderLine('Content-Length');

            if ((string)(int)$string !== $string) {
                // Content-Length value is not an integer or not a single integer
                throw new \InvalidArgumentException('The value of `Content-Length` is not valid', Response::STATUS_BAD_REQUEST);
            }
        }

        return $request;
    }
}
