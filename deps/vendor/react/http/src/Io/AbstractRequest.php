<?php

namespace React\Http\Io;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Message\Uri;

/**
 * [Internal] Abstract HTTP request base class (PSR-7)
 *
 * @internal
 * @see RequestInterface
 */
abstract class AbstractRequest extends AbstractMessage implements RequestInterface
{
    /** @var ?string */
    private $requestTarget;

    /** @var string */
    private $method;

    /** @var UriInterface */
    private $uri;

    /**
     * @param string $method
     * @param string|UriInterface $uri
     * @param array<string,string|string[]> $headers
     * @param StreamInterface $body
     * @param string unknown $protocolVersion
     */
    protected function __construct(
        $method,
        $uri,
        array $headers,
        StreamInterface $body,
        $protocolVersion
    ) {
        if (\is_string($uri)) {
            $uri = new Uri($uri);
        } elseif (!$uri instanceof UriInterface) {
            throw new \InvalidArgumentException(
                'Argument #2 ($uri) expected string|Psr\Http\Message\UriInterface'
            );
        }

        // assign default `Host` request header from URI unless already given explicitly
        $host = $uri->getHost();
        if ($host !== '') {
            foreach ($headers as $name => $value) {
                if (\strtolower($name) === 'host' && $value !== array()) {
                    $host = '';
                    break;
                }
            }
            if ($host !== '') {
                $port = $uri->getPort();
                if ($port !== null && (!($port === 80 && $uri->getScheme() === 'http') || !($port === 443 && $uri->getScheme() === 'https'))) {
                    $host .= ':' . $port;
                }

                $headers = array('Host' => $host) + $headers;
            }
        }

        parent::__construct($protocolVersion, $headers, $body);

        $this->method = $method;
        $this->uri = $uri;
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if (($query = $this->uri->getQuery()) !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget($requestTarget)
    {
        if ((string) $requestTarget === $this->requestTarget) {
            return $this;
        }

        $request = clone $this;
        $request->requestTarget = (string) $requestTarget;

        return $request;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        if ((string) $method === $this->method) {
            return $this;
        }

        $request = clone $this;
        $request->method = (string) $method;

        return $request;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $request = clone $this;
        $request->uri = $uri;

        $host = $uri->getHost();
        $port = $uri->getPort();
        if ($port !== null && $host !== '' && (!($port === 80 && $uri->getScheme() === 'http') || !($port === 443 && $uri->getScheme() === 'https'))) {
            $host .= ':' . $port;
        }

        // update `Host` request header if URI contains a new host and `$preserveHost` is false
        if ($host !== '' && (!$preserveHost || $request->getHeaderLine('Host') === '')) {
            // first remove all headers before assigning `Host` header to ensure it always comes first
            foreach (\array_keys($request->getHeaders()) as $name) {
                $request = $request->withoutHeader($name);
            }

            // add `Host` header first, then all other original headers
            $request = $request->withHeader('Host', $host);
            foreach ($this->withoutHeader('Host')->getHeaders() as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        return $request;
    }
}
