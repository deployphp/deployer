<?php
/*

### Configuration

- `cloudflare` – array with configuration for cloudflare
    - `service_key` – Cloudflare Service Key. If this is not provided, use api_key and email.
    - `api_key` – Cloudflare API key generated on the "My Account" page.
    - `email` – Cloudflare Email address associated with your account.
    - `api_token` – Cloudflare API Token generated on the "My Account" page.
    - `domain` – The domain you want to clear (optional if zone_id is provided).
    - `zone_id` – Cloudflare Zone ID (optional).

### Usage

Since the website should be built and some load is likely about to be applied to your server, this should be one of,
if not the, last tasks before cleanup

*/
namespace Deployer;

desc('Clears Cloudflare Cache');
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
    } else if (!empty($config['api_token'])) {
        $headers = [
            'Authorization' => 'Bearer '.$config['api_token']
        ];
    } else {
        throw new \RuntimeException("Set a service key or email / api key");
    }

    $headers['Content-Type'] = 'application/json';

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

    $zoneId = $config['zone_id'];
    if (empty($zoneId)) {
        if (empty($config['domain'])) {
            throw new \RuntimeException("Set a domain");
        }

        // get the mysterious zone id from Cloud Flare
        $zones = json_decode($makeRequest(
            "zones?name={$config['domain']}"
        ), true);

        if (!empty($zones['errors'])) {
            throw new \RuntimeException('Problem with zone data');
        } else {
            $zoneId = current($zones['result'])['id'];
        }
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
