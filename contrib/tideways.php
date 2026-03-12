<?php

/*
### Configuration options

- **api_key** *(required)*: Tideways API key for authentication.
- **version** *(required)*: A version identifier for this release. Can be a version number, a commit hash etc.
- **environment** *(optional)*: The environment you're deploying to. Defaults to 'production'.
- **service** *(optional)*: The service name for the release. Defaults to 'web'.
- **compare_after_minutes** *(optional)*: Time in minutes to compare performance before/after release. Defaults to 90.
- **project** *(optional)*: Project name/path for the description field.
- **description** *(optional)*: Custom description for the release.
- **tideways_server** *(optional)*: Tideways server URL. Defaults to 'https://app.tideways.io'.
- **git_version_command** *(optional)*: The command that retrieves the git version information.

```php
// deploy.php

set('tideways', [
    'api_key' => 'your-api-key',
    'version' => '',
    'environment' => 'production',
    'service' => 'web',
    'compare_after_minutes' => 90,
]);

 */

namespace Deployer;

use Deployer\Utility\Httpie;

desc('Notifies Tideways of deployment');
task('deploy:tideways', static function () {
    $defaultConfig = [
        'version' => getReleaseGitRef(),
        'environment' => 'production',
        'service' => 'web',
        'compare_after_minutes' => 90,
        'project' => null,
        'description' => null,
        'tideways_server' => 'https://app.tideways.io',
    ];

    $config = array_merge($defaultConfig, (array)get('tideways'));
    array_walk(
        $config,
        static function (&$value) use ($config) {
            if (is_callable($value)) {
                $value = $value($config);
            }
        },
    );

    if (!isset($config['api_key']) || empty($config['api_key'])) {
        writeln('<comment>Skipping Tideways release creation: api_key not set</comment>');

        return;
    }

    if (!isset($config['version']) || empty($config['version'])) {
        throw new \RuntimeException(
            <<<EXAMPLE
                        Required data missing. Please configure tideways:
                        set(
                            'tideways',
                            [
                                'api_key' => 'your-api-key',
                                'version' => '1.0.0',
                            ]
                        );
                        EXAMPLE,
        );
    }

    $payload = [
        'apiKey' => $config['api_key'],
        'name' => $config['version'],
        'type' => 'release',
        'environment' => $config['environment'],
        'service' => $config['service'],
        'compareAfterMinutes' => (int)$config['compare_after_minutes'],
    ];

    // Add description if provided or generate from project
    if (!empty($config['description'])) {
        $payload['description'] = $config['description'];
    } elseif (!empty($config['project'])) {
        $payload['description'] = "Release {$config['version']} for project {$config['project']}";
    }

    $eventsApiUrl = $config['tideways_server'] . '/api/events';

    try {
        Httpie::post($eventsApiUrl)
            ->setopt(CURLOPT_TIMEOUT, 10)
            ->header('Content-Type', 'application/json')
            ->body(json_encode($payload))
            ->send();

        writeln(
            sprintf(
                '<info>Tideways:</info> Release <comment>%s</comment> ' .
                'for environment <comment>%s</comment> and service <comment>%s</comment> created successfully.',
                $config['version'],
                $config['environment'],
                $config['service'],
            ),
        );
    } catch (\Throwable $e) {
        writeln('<error>Failed to create Tideways release: ' . $e->getMessage() . '</error>');
        throw $e;
    }
});

function getReleaseGitRef(): \Closure
{
    return static function ($config = []): string {
        if (get('update_code_strategy') === 'archive') {
            if (isset($config['git_version_command'])) {
                cd('{{deploy_path}}/.dep/repo');

                return trim(run($config['git_version_command']));
            }

            return run('cat {{current_path}}/REVISION');
        }

        cd('{{release_path}}');

        if (isset($config['git_version_command'])) {
            return trim(run($config['git_version_command']));
        }

        return trim(run('git log -n 1 --format="%h"'));
    };
}

after('deploy:success', 'deploy:tideways');
