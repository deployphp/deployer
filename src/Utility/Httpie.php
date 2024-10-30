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
    private string $method = 'GET';
    private string $url = '';
    private array $headers = [];
    private string $body = '';
    private array $curlopts = [];
    private bool $nothrow = false;

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \Exception(
                "Please, install curl extension.\n" .
                "https://php.net/curl.installation",
            );
        }
    }

    public static function get(string $url): Httpie
    {
        $http = new self();
        $http->method = 'GET';
        $http->url = $url;
        return $http;
    }

    public static function post(string $url): Httpie
    {
        $http = new self();
        $http->method = 'POST';
        $http->url = $url;
        return $http;
    }

    public static function patch(string $url): Httpie
    {
        $http = new self();
        $http->method = 'PATCH';
        $http->url = $url;
        return $http;
    }


    public static function put(string $url): Httpie
    {
        $http = new self();
        $http->method = 'PUT';
        $http->url = $url;
        return $http;
    }

    public static function delete(string $url): Httpie
    {
        $http = new self();
        $http->method = 'DELETE';
        $http->url = $url;
        return $http;
    }

    public function query(array $params): self
    {
        $this->url .= '?' . http_build_query($params);
        return $this;
    }

    public function header(string $header, string $value): self
    {
        $this->headers[$header] = $value;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        $this->headers = array_merge($this->headers, [
            'Content-Length' => strlen($this->body),
        ]);
        return $this;
    }

    public function jsonBody(array $data): self
    {
        $this->body = json_encode($data, JSON_PRETTY_PRINT);
        $this->headers = array_merge($this->headers, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($this->body),
        ]);
        return $this;
    }

    public function formBody(array $data): self
    {
        $this->body = http_build_query($data);
        $this->headers = array_merge($this->headers, [
            'Content-type' => 'application/x-www-form-urlencoded',
            'Content-Length' => strlen($this->body),
        ]);
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

    public function send(?array &$info = null): string
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
        $info = curl_getinfo($ch);
        if ($result === false) {
            if ($this->nothrow) {
                $result = '';
            } else {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                throw new HttpieException($error, $errno);
            }
        }
        curl_close($ch);
        return $result;
    }

    public function getJson(): mixed
    {
        $result = $this->send();
        $response = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpieException(
                'JSON Error: ' . json_last_error_msg() . '\n' .
                'Response: ' . $result,
            );
        }
        return $response;
    }
}
