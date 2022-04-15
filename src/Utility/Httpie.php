<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Exception\HttpieException;

class Httpie
{
    /**
     * @var string
     */
    private $method = 'GET';
    /**
     * @var string
     */
    private $url = '';
    /**
     * @var string
     */
    private $query = '';
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var string|null
     */
    private $body;
    /**
     * @var array
     */
    private $curlopts = [];
    /**
     * @var bool
     */
    private $nothrow = false;

    private function __construct(string $method, string $url)
    {
        if (!extension_loaded('curl')) {
            throw new \Exception(
                "Please, install curl extension.\n" .
                "https://goo.gl/yTAeZh"
            );
        }

        $this->method = $method;
        $this->url = $url;
    }

    public static function get(string $url): Httpie
    {
        return self::request('GET', $url);
    }

    public static function post(string $url): Httpie
    {
        return self::request('POST', $url);
    }
    
    public static function patch(string $url): Httpie
    {
        return self::request('PATCH', $url);
    }

    public static function request(string $method, string $url): Httpie
    {
        return new self($method , $url);
    }
    
    public function query(array $params): Httpie
    {
        $http = clone $this;
        $http->query = '?' . http_build_query($params);
        return $http;
    }

    public function header(string $header, string $value): Httpie
    {
        $http = clone $this;
        $http->headers[$header] = $value;
        return $http;
    }

    public function body(string $body, string $type = 'application/json'): Httpie
    {
        $http = clone $this;
        $http->body = $body;
        $http->headers['Content-Type'] = $type;
        return $http;
    }

    public function jsonBody(array $data): Httpie
    {
        try {
            return $this->body(json_encode($data, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new HttpieException('JSON Encode Error: ' . $e->getMessage());
        }
    }

    public function formBody(array $data): Httpie
    {
        return $this->body(\http_build_query($data), 'application/x-www-form-urlencoded');
    }

    /**
     * @param mixed $value
     */
    public function setopt(int $key, $value): Httpie
    {
        $http = clone $this;
        $http->curlopts[$key] = $value;
        return $http;
    }

    public function nothrow(bool $on = true): Httpie
    {
        $http = clone $this;
        $http->nothrow = $on;
        return $http;
    }

    private function joinHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        return $headers;
    }

    public function send(?array &$info = null): string
    {
        $options = [
            CURLOPT_USERAGENT      => 'Deployer ' . DEPLOYER_VERSION,
            CURLOPT_CUSTOMREQUEST  => $this->method,
            CURLOPT_HTTPHEADER     => $this->joinHeaders(),
            CURLOPT_ENCODING       => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5
        ];


        if (isset($this->body)) {
            $options[CURLOPT_POSTFIELDS] = $this->body;
        }

        if ($this->curlopts) {
            $options = $this->curlopts + $options;
        }

        $ch = curl_init($this->url.$this->query);
        curl_setopt_array($ch, $options);

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

    /**
     * @return mixed
     */
    public function getJson()
    {
        $this->headers['Accept'] = 'application/json';
        try {
            return json_decode($this->send(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new HttpieException('JSON Decode Error: ' . $e->getMessage());
        }
    }
}
