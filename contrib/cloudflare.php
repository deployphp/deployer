<?php
/* (c) David Jordan / CyberDuck <david@cyber-duck.co.uk>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Clearing Cloudflare Cache');
task('deploy:cloudflare', function () {

    $config = get('cloudflare', []);

    // validate config and set headers
    if (!empty($config['service_key'])) {
        $headers = [
            'X-Auth-User-Service-Key' => $config['service_key']
        ];
    } elseif (!empty($config['email']) && !empty($config['api_key'])) {
        $headers = [
            'X-Auth-Key'   => $config['api_key'],
            'X-Auth-Email' => $config['email']
        ];
    } else {
        throw new \RuntimeException("Set a service key or email / api key");
    }

    $headers['Content-Type'] = 'application/json';

    if (empty($config['domain'])) {
        throw new \RuntimeException("Set a domain");
    }

    $makeRequest = function ($url, $opts = []) use ($headers) {
        $ch = curl_init("https://api.cloudflare.com/client/v4/$url");

        $parsedHeaders = [];
        foreach($headers as $key => $value){
            $parsedHeaders[] = "$key: $value";
        }

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $parsedHeaders,
            CURLOPT_RETURNTRANSFER => true
        ]);

        curl_setopt_array($ch, $opts);

        $res = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException("Error making curl request (result: $res)");
        }

        curl_close($ch);

        return $res;
    };

    // get the mysterious zone id from Cloud Flare
    $zones = json_decode($makeRequest(
        "zones?name={$config['domain']}"
    ), true);

    if (empty($zones['success']) || !empty($zones['errors'])) {
        throw new \RuntimeException("Problem with zone data");
    } else {
        $zoneId = current($zones['result'])['id'];
    }

    // make purge request
    $makeRequest(
        "zones/$zoneId/purge_cache",
        [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS    => json_encode(
                [
                    'purge_everything' => true
                ]
            ),
        ]
    );
});
