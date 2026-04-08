<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Exception\HttpieException;

class Httpie
{
    private static bool $extensionChecked = false;

    private string $method = 'GET';
    private string $url = '';
    private array $headers = [];
    private string $body = '';
    private array $curlopts = [];
    private bool $nothrow = false;

    public function __construct()
    {
        if (!self::$extensionChecked) {
            if (!extension_loaded('curl')) {
                throw new \Exception(
                    "Please, install curl extension.\n"
                    . "https://php.net/curl.installation",
                );
            }
            self::$extensionChecked = true;
        }
    }

    public static function get(string $url): self
    {
        $http = new self();
        $http->method = 'GET';
        $http->url = $url;
        return $http;
    }

    public static function post(string $url): self
    {
        $http = new self();
        $http->method = 'POST';
        $http->url = $url;
        return $http;
    }

    public static function patch(string $url): self
    {
        $http = new self();
        $http->method = 'PATCH';
        $http->url = $url;
        return $http;
    }

    public static function put(string $url): self
    {
        $http = new self();
        $http->method = 'PUT';
        $http->url = $url;
        return $http;
    }

    public static function delete(string $url): self
    {
        $http = new self();
        $http->method = 'DELETE';
        $http->url = $url;
        return $http;
    }

    public function query(array $params): self
    {
        $separator = str_contains($this->url, '?') ? '&' : '?';
        $this->url .= $separator . http_build_query($params);
        return $this;
    }

    public function header(string $header, string $value): self
    {
        $this->headers[$header] = $value;
        return $this;
    }

    public function bearerToken(string $token): self
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    public function basicAuth(string $user, string $pass): self
    {
        $this->curlopts[CURLOPT_USERPWD] = "$user:$pass";
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->curlopts[CURLOPT_TIMEOUT] = $seconds;
        $this->curlopts[CURLOPT_CONNECTTIMEOUT] = $seconds;
        return $this;
    }

    public function noTimeout(): self
    {
        $this->curlopts[CURLOPT_TIMEOUT] = 0;
        $this->curlopts[CURLOPT_CONNECTTIMEOUT] = 0;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        $this->headers['Content-Length'] = (string) strlen($this->body);
        return $this;
    }

    public function jsonBody(array $data): self
    {
        $this->body = json_encode($data, JSON_PRETTY_PRINT);
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Content-Length'] = (string) strlen($this->body);
        return $this;
    }

    public function formBody(array $data): self
    {
        $this->body = http_build_query($data);
        $this->headers['Content-type'] = 'application/x-www-form-urlencoded';
        $this->headers['Content-Length'] = (string) strlen($this->body);
        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setopt(int $key, $value): self
    {
        $this->curlopts[$key] = $value;
        return $this;
    }

    public function nothrow(bool $on = true): self
    {
        $this->nothrow = $on;
        return $this;
    }

    /**
     * Send the request and return a response object.
     */
    public function send(?array &$info = null): HttpResponse
    {
        if ($this->url === '') {
            throw new \RuntimeException('URL must not be empty to Httpie::send()');
        }
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Deployer ' . DEPLOYER_VERSION);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        foreach ($this->curlopts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $result = curl_exec($ch);
        $info = curl_getinfo($ch) ?: [];
        if ($result === false) {
            if ($this->nothrow) {
                return new HttpResponse('', $info);
            }
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            throw new HttpieException($error, $errno);
        }
        return new HttpResponse($result, $info);
    }

    /**
     * Send the request and return the decoded JSON response.
     */
    public function sendJson(): mixed
    {
        return $this->send()->json();
    }

    /**
     * @deprecated Use sendJson() instead.
     */
    public function getJson(): mixed
    {
        return $this->sendJson();
    }

    // Getters for testing.

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCurlopts(): array
    {
        return $this->curlopts;
    }
}
