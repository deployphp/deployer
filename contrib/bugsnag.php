<?php
/*

## Configuration

- *bugsnag_api_key* – the API Key associated with the project. Informs Bugsnag which project has been deployed. This is the only required field.
- *bugsnag_provider* – the name of your source control provider. Required when repository is supplied and only for on-premise services.
- *bugsnag_app_version* – the app version of the code you are currently deploying. Only set this if you tag your releases with semantic version numbers and deploy infrequently. (Optional.)

## Usage

Since you should only notify Bugsnag of a successful deployment, the `bugsnag:notify` task should be executed right at the end.

```php
after('deploy', 'bugsnag:notify');
```
*/
namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifies Bugsnag of deployment');
task('bugsnag:notify', function () {
    $data = [
        'apiKey'       => get('bugsnag_api_key'),
        'releaseStage' => get('target'),
        'repository'   => get('repository'),
        'provider'     => get('bugsnag_provider', ''),
        'branch'       => get('branch'),
        'revision'     => runLocally('git log -n 1 --format="%h"'),
        'appVersion'   => get('bugsnag_app_version', ''),
    ];

    Httpie::post('https://notify.bugsnag.com/deploy')
        ->jsonBody($data)
        ->send();
});
