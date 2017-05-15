<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Exception\Exception;

/**
 * @codeCoverageIgnore
 */
class Request
{
    /**
     * @param string $url
     * @param array $query Query params for request
     * @return array
     */
    public static function get($url, $query)
    {
        return self::curl('GET', $url, $query);
    }

    /**
     * @param string $url
     * @param array $data Post fields data, send as json with `Content-Type: application/json`.
     * @return array
     */
    public static function post($url, $data)
    {
        return self::curl('POST', $url, [], $data);
    }

    private static function curl($method, $url, $query = [], $data = [])
    {
        if (!extension_loaded('curl')) {
            throw new Exception("Please, install curl extension.\nhttps://goo.gl/yTAeZh");
        }

        $ch = curl_init($url . '?' . http_build_query($query));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method === 'POST' ? 'POST' : 'GET');
        if (!empty($data)) {
            $body = json_encode($data, JSON_PRETTY_PRINT);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body)
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        $response = @json_decode($result, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $response;
    }
}
