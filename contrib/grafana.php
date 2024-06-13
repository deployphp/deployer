<?php
/*

## Configuration options

- **url** *(required)*: the URL to the creates annotation api endpoint.
- **token** *(required)*: authentication token. Can be created at Grafana Console.
- **time** *(optional)* – set deploy time of annotation. specify epoch milliseconds. (Defaults is set to the current time in epoch milliseconds.)
- **tags** *(optional)* – set tag of annotation.
- **text** *(optional)* – set text of annotation. (Defaults is set to "Deployed " + git log -n 1 --format="%h")

```php
// deploy.php

set('grafana', [
    'token' => 'eyJrIj...',
    'url' => 'http://grafana/api/annotations',
    'tags' => ['deploy', 'production'],
]);

```

## Usage

If you want to create annotation about successful end of deployment.

```php
after('deploy:success', 'grafana:annotation');
```

*/
namespace Deployer;

use Deployer\Utility\Httpie;


desc('Creates Grafana annotation of deployment');
task('grafana:annotation', function () {
    $defaultConfig = [
        'url' => null,
        'token' => null,
        'time' => round(microtime(true) * 1000),
        'tags' => [],
        'text' => null,
    ];

    $config = array_merge($defaultConfig, (array) get('grafana'));
    if (!is_array($config) || !isset($config['url']) || !isset($config['token'])) {
        throw new \RuntimeException("Please configure Grafana: set('grafana', ['url' => 'https://localhost/api/annotations', token' => 'eyJrIjo...']);");
    }

    $params = [
        'time' => $config['time'],
        'isRegion' => false,
        'tags' => $config['tags'],
        'text' => $config['text'],
    ];
    if (!isset($params['text'])) {
        $params['text'] = 'Deployed ' . trim(runLocally('git log -n 1 --format="%h"'));
    }

    Httpie::post($config['url'])
        ->header('Authorization', 'Bearer ' . $config['token'])
        ->header('Content-type', 'application/json')
        ->jsonBody($params)
        ->send();
});
