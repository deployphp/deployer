<?php
/*
## Configuration

- `newrelic_app_id` – newrelic's app id
- `newrelic_api_key` – newrelic's api key
- `newrelic_description` – message to send
- `newrelic_endpoint` – newrelic's REST API endpoint

## Usage

Since you should only notify New Relic of a successful deployment, the `newrelic:notify` task should be executed right at the end.

```php
after('deploy', 'newrelic:notify');
```

 */
namespace Deployer;

use Deployer\Utility\Httpie;

set('newrelic_app_id', function () {
    throw new \Exception('Please, configure "newrelic_app_id" parameter.');
});

set('newrelic_description', function () {
    return runLocally('git log -n 1 --format="%an: %s" | tr \'"\' "\'"');
});

set('newrelic_revision', function () {
    return runLocally('git log -n 1 --format="%h"');
});

set('newrelic_endpoint', 'api.newrelic.com');

desc('Notifies New Relic of deployment');
task('newrelic:notify', function () {
    if (($appId = get('newrelic_app_id')) && ($apiKey = get('newrelic_api_key')) && ($endpoint = get('newrelic_endpoint'))) {
        $data = [
            'user' => get('user'),
            'revision' => get('newrelic_revision'),
            'description' => get('newrelic_description'),
        ];

        Httpie::post("https://$endpoint/v2/applications/$appId/deployments.json")
            ->header("X-Api-Key", $apiKey)
            ->query(['deployment' => $data])
            ->send();
    }
})
    ->once()
    ->hidden();
