<?php
/*

### Configuration options

- **api_key** *(required)*: Check to "Project Settings" to find the API key necessary to authenticate the Create Event request.
- **name** *(optional)*: Name of the release, defaults to Deployer release name
- **name_prefix** *(optional)*: a string prefixed to version.
- **type** *(optional)*: Type of the event. Currently supports `release` and `marker`, defaults to `release`.
- **description** *(optional)*: More details about the release.
- **environment** *(optional)*: The environment this release is performed on, otherwise the default environment is used.
- **service** *(optional)*: The service this release is performed on, otherwise the default service of the project is used.
- **compareAfterMinutes** *(optional)*: Defaults to 90 minutes. Specifies the timeframes around the event for which the performance will be compared. Supported values are between 5 minutes and 1440 minutes (one day).

```php
// deploy.php

set('tideways', [
    'api_key' => 'Qm8JxL2...'
]);
```

### Suggested Usage

Since you should only notify Tideways of a successful deployment, the deploy:tideways task should be executed right at the end.

```php
// deploy.php

after('deploy', 'deploy:tideways');
```

 */

namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifies Tideways of deployment');
task(
    'deploy:tideways',
    static function () {
        $defaultConfig = [
            'name' => get('release_name'),
            'type' => 'release',
            'description' => null,
            'environment' => null,
            'service' => null,
            'compareAfterMinutes' => 90,
        ];

        $config = array_merge($defaultConfig, (array) get('tideways'));
        array_walk(
            $config,
            static function (&$value) use ($config) {
                if (is_callable($value)) {
                    $value = $value($config);
                }
            },
        );

        if (
            !isset($config['api_key'])
            || empty($config['api_key'])
        ) {
            throw new \RuntimeException(
                <<<EXAMPLE
                    Required data missing. Please configure tideways:
                    set(
                        'tideways',
                        [
                            'api_key' => 'Qm8JxL2...',
                        ]
                    );"
                    EXAMPLE,
            );
        }

        $releaseData = array_filter(
            [
                'apiKey' => $config['api_key'],
                'name' => ($config['name_prefix'] ?? '') . $config['name'],
                'description' => $config['description'],
                'type' => $config['type'],
                'environment' => $config['environment'],
                'service' => $config['service'],
                'compareAfterMinutes' => $config['compareAfterMinutes'],
            ],
        );

        $response = Httpie::post('https://app.tideways.io/api/events')
            ->setopt(CURLOPT_TIMEOUT, 10)
            ->jsonBody($releaseData)
            ->getJson();

        if (!isset($response['ok'], $response['id']) || $response['ok'] !== true) {
            throw new \RuntimeException(sprintf('Unable to create a release: %s', print_r($response, true)));
        }

        writeln(
            sprintf(
                '<info>Tideways:</info> Release of version <comment>%s</comment> '
                . 'for project: <comment>%s</comment> created successfully.',
                $releaseData['name'],
                $response['name'],
            ),
        );
    },
);
