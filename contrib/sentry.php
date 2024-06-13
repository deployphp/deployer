<?php
/*

### Configuration options

- **organization** *(required)*: the slug of the organization the release belongs to.
- **projects** *(required)*: array of slugs of the projects to create a release for.
- **token** *(required)*: authentication token. Can be created at [https://sentry.io/settings/account/api/auth-tokens/]
- **version** *(required)* – a version identifier for this release.
Can be a version number, a commit hash etc. (Defaults is set to git log -n 1 --format="%h".)
- **version_prefix** *(optional)* - a string prefixed to version.
Releases are global per organization so indipentent projects needs to prefix version number with unique string to avoid conflicts
- **environment** *(optional)* - the environment you’re deploying to. By default framework's environment is used.
For example for symfony, *symfony_env* configuration is read otherwise defaults to 'prod'.
- **ref** *(optional)* – an optional commit reference. This is useful if a tagged version has been provided.
- **refs** *(optional)* - array to indicate the start and end commits for each repository included in a release.
Head commits must include parameters *repository* and *commit*) (the HEAD sha).
They can optionally include *previousCommit* (the sha of the HEAD of the previous release),
which should be specified if this is the first time you’ve sent commit data.
- **commits** *(optional)* - array commits data to be associated with the release.
Commits must include parameters *id* (the sha of the commit), and can optionally include *repository*,
*message*, *author_name*, *author_email* and *timestamp*. By default will send all new commits,
unless it's a first release, then only first 200 will be sent.
- **url** *(optional)* – a URL that points to the release. This can be the path to an online interface to the sourcecode for instance.
- **date_released** *(optional)* – date that indicates when the release went live. If not provided the current time is assumed.
- **sentry_server** *(optional)* – sentry server (if you host it yourself). defaults to hosted sentry service.
- **date_deploy_started** *(optional)* - date that indicates when the deploy started. Defaults to current time.
- **date_deploy_finished** *(optional)* - date that indicates when the deploy ended. If not provided, the current time is used.
- **deploy_name** *(optional)* - name of the deploy
- **git_version_command** *(optional)* - the command that retrieves the git version information (Defaults is set to git log -n 1 --format="%h", other options are git describe --tags --abbrev=0)

```php
// deploy.php

set('sentry', [
    'organization' => 'exampleorg',
    'projects' => [
        'exampleproj'
    ],
    'token' => 'd47828...',
    'version' => '0.0.1',

]);
```

### Suggested Usage

Since you should only notify Sentry of a successful deployment, the deploy:sentry task should be executed right at the end.

```php
// deploy.php

after('deploy', 'deploy:sentry');
```

 */
namespace Deployer;

use Closure;
use DateTime;
use Deployer\Exception\ConfigurationException;
use Deployer\Utility\Httpie;

desc('Notifies Sentry of deployment');
task(
    'deploy:sentry',
    static function () {
        $now = date('c');

        $defaultConfig = [
            'version' => getReleaseGitRef(),
            'version_prefix' => null,
            'refs' => [],
            'ref' => null,
            'commits' => getGitCommitsRefs(),
            'url' => null,
            'date_released' => $now,
            'date_deploy_started' => $now,
            'date_deploy_finished' => $now,
            'sentry_server' => 'https://sentry.io',
            'previous_commit' => null,
            'environment' => get('symfony_env', 'prod'),
            'deploy_name' => null,
        ];

        $config = array_merge($defaultConfig, (array) get('sentry'));
        array_walk(
            $config,
            static function (&$value) use ($config) {
                if (is_callable($value)) {
                    $value = $value($config);
                }
            }
        );

        if (
            !isset($config['organization'], $config['token'], $config['version'])
            || (empty($config['projects']) || !is_array($config['projects']))
        ) {
            throw new \RuntimeException(
                <<<EXAMPLE
Required data missing. Please configure sentry:
set(
    'sentry',
    [
        'organization' => 'exampleorg',
        'projects' => [
            'exampleproj',
            'exampleproje2'
        ],
        'token' => 'd47828...',
    ]
);"
EXAMPLE
            );
        }

        $releaseData = array_filter(
            [
                'version' => ($config['version_prefix'] ?? '') . $config['version'],
                'refs' => $config['refs'],
                'ref' => $config['ref'],
                'url' => $config['url'],
                'commits' => array_slice($config['commits'] ?? [], 0), // reset keys to serialize as array in json
                'dateReleased' => $config['date_released'],
                'projects' => $config['projects'],
                'previousCommit' => $config['previous_commit'],
            ]
        );

        $releasesApiUrl = $config['sentry_server'] . '/api/0/organizations/' . $config['organization'] . '/releases/';
        $response = Httpie::post(
            $releasesApiUrl
        )
            ->header('Authorization', sprintf('Bearer %s', $config['token']))
            ->jsonBody($releaseData)
            ->getJson();

        if (!isset($response['version'], $response['projects'])) {
            throw new \RuntimeException(sprintf('Unable to create a release: %s', print_r($response, true)));
        }

        writeln(
            sprintf(
                '<info>Sentry:</info> Release of version <comment>%s</comment> ' .
                'for projects: <comment>%s</comment> created successfully.',
                $response['version'],
                implode(', ', array_column($response['projects'], 'slug'))
            )
        );

        $deployData = array_filter(
            [
                'environment' => $config['environment'],
                'name' => $config['deploy_name'],
                'url' => $config['url'],
                'dateStarted' => $config['date_deploy_started'],
                'dateFinished' => $config['date_deploy_finished'],
            ]
        );

        $response = Httpie::post(
            $releasesApiUrl . $response['version'] . '/deploys/'
        )
            ->header('Authorization', sprintf('Bearer %s', $config['token']))
            ->jsonBody($deployData)
            ->getJson();

        if (!isset($response['id'], $response['environment'])) {
            throw new \RuntimeException(sprintf('Unable to create a deployment: %s', print_r($response, true)));
        }

        writeln(
            sprintf(
                '<info>Sentry:</info> Deployment <comment>%s</comment> ' .
                'for environment <comment>%s</comment> created successfully',
                $response['id'],
                $response['environment']
            )
        );
    }
);

function getPreviousReleaseRevision()
{
    switch (get('update_code_strategy')) {
        case 'archive':
            if (has('previous_release')) {
                return run('cat {{previous_release}}/REVISION');
            }

            return null;
        case 'clone':
            if (has('previous_release')) {
                cd('{{previous_release}}');
                return trim(run('git rev-parse HEAD'));
            }

            return null;
        default:
            throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
    }
}

function getCurrentReleaseRevision()
{
    switch (get('update_code_strategy')) {
        case 'archive':
            return run('cat {{release_path}}/REVISION');

        case 'clone':
            cd('{{release_path}}');
            return trim(run('git rev-parse HEAD'));

        default:
            throw new ConfigurationException(parse("Unknown `update_code_strategy` option: {{update_code_strategy}}."));
    }
}

function getReleaseGitRef(): Closure
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

function getGitCommitsRefs(): Closure
{
    return static function ($config = []): array {
        $previousReleaseRevision = getPreviousReleaseRevision();
        $currentReleaseRevision = getCurrentReleaseRevision() ?: 'HEAD';

        if ($previousReleaseRevision === null) {
            $commitRange = $currentReleaseRevision;
        } else {
            $commitRange = $previousReleaseRevision . '..' . $currentReleaseRevision;
        }

        try {
            if (get('update_code_strategy') === 'archive') {
                cd('{{deploy_path}}/.dep/repo');
            }
            else {
                cd('{{release_path}}');
            }

            $result = run(sprintf('git rev-list --pretty="%s" %s', 'format:%H#%an#%ae#%at#%s', $commitRange));
            $lines = array_filter(
            // limit number of commits for first release with many commits
                array_map('trim', array_slice(explode("\n", $result), 0, 200)),
                static function (string $line): bool {
                    return !empty($line) && strpos($line, 'commit') !== 0;
                }
            );

            return array_map(
                static function (string $line): array {
                    [$ref, $authorName, $authorEmail, $timestamp, $message] = explode('#', $line, 5);

                    return [
                        'id' => $ref,
                        'author_name' => $authorName,
                        'author_email' => $authorEmail,
                        'message' => $message,
                        'timestamp' => date(\DateTime::ATOM, (int) $timestamp),
                    ];
                },
                $lines
            );

        } catch (\Deployer\Exception\RunException $e) {
            writeln($e->getMessage());
            return [];
        }
    };
}
