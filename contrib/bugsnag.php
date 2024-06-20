<?php
/*

## Configuration

- *bugsnag_api_key* – the API Key associated with the project. Informs BugSnag which project has been deployed. This is the only required field.
- *bugsnag_provider* – the name of your source control provider. Required when repository is supplied and only for on-premise services.
- *bugsnag_app_version* – the app version of the code you are currently deploying. Only set this if you tag your releases with semantic version numbers and deploy infrequently. (Optional.)

If you use Laravel, follow the official [BugSnag integration guide](https://docs.bugsnag.com/platforms/php/laravel).

## Usage

Since you should only notify BugSnag of a successful deployment, the `bugsnag:notify` task should be executed right at the end.

```php
after('deploy', 'bugsnag:notify');
```

If you want to use the Laravel Artisan command, call the `artisan:bugsnag:deploy` task to notify BugSnag of new releases.
Please note that you have to manually register the BugSnag `DeployCommand` command in your `app/Console/Kernel.php` file.

```php
after('deploy', 'artisan:bugsnag:deploy');
```

*/
namespace Deployer;

use Deployer\Utility\Httpie;

/*
 * We will extend the Laravel recipe to simply add BugSnag
 * provided Artisan command.
 */
require_once __DIR__ . '/../recipe/laravel.php';

desc('Notifies BugSnag of deployment');
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

desc('Notifies BugSnag of releases using the Laravel Artisan console');
task('artisan:bugsnag:deploy', function () {
	$data = [
		'repository' => get('repository'),
		'revision'   => get('release_revision'),
		'provider'   => get('bugsnag_provider'),
		'builder'    => get('user')
	];

	$options = '';
	foreach ($data as $key => $value) {
		if ($value !== null) {
			$options .= " --$key=\"$value\"";
		}
	}

	artisan("bugsnag:deploy $options")();
});
