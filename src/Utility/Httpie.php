<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

class Httpie
{
    private $method = 'GET';
    private $url = '';
    private $headers = [];
    private $body = '';
    private $curlopts = [];

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \Exception(
                "Please, install curl extension.\n" .
                "https://goo.gl/yTAeZh"
            );
        }
    }

    public static function get(string $url): Httpie
    {
        $http = new self;
        $http->method = 'GET';
        $http->url = $url;
        return $http;
    }

    public static function post(string $url): Httpie
    {
        $http = new self;
        $http->method = 'POST';
        $http->url = $url;
        return $http;
    }

    public function query(array $params): Httpie
    {
        $http = clone $this;
        $http->url .= '?' . http_build_query($params);
        return $http;
    }

    public function header(string $header): Httpie
    {
        $http = clone $this;
        $http->headers[] = $header;
        return $http;
    }

    public function body(array $data): Httpie
    {
        $http = clone $this;
        $http->body = json_encode($data, JSON_PRETTY_PRINT);
        $http->headers = array_merge($http->headers, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($http->body)
        ]);
        return $http;
    }

    public function form(array $data): Httpie
    {
        $http = clone $this;
        $http->body = http_build_query($data);
        $http->headers = array_merge($this->headers, [
            'Content-type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($http->body)
        ]);
        return $http;
    }

    public function setopt($key, $value)
    {
        $http = clone $this;
        $http->curlopts[$key] = $value;
        return $http;
    }

    public function send()
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
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
        curl_close($ch);
        return $result;
    }

    public function getJson()
    {
        $result = $this->send();
        $response = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON Error: ' . json_last_error_msg());
        }
        return $response;
    }
}
