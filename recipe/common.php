<?php
namespace Deployer;

require __DIR__ . '/provision.php';
require __DIR__ . '/deploy/check_remote.php';
require __DIR__ . '/deploy/cleanup.php';
require __DIR__ . '/deploy/clear_paths.php';
require __DIR__ . '/deploy/copy_dirs.php';
require __DIR__ . '/deploy/info.php';
require __DIR__ . '/deploy/lock.php';
require __DIR__ . '/deploy/push.php';
require __DIR__ . '/deploy/release.php';
require __DIR__ . '/deploy/rollback.php';
require __DIR__ . '/deploy/setup.php';
require __DIR__ . '/deploy/shared.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/update_code.php';
require __DIR__ . '/deploy/vendors.php';
require __DIR__ . '/deploy/writable.php';

use Deployer\Exception\ConfigurationException;
use Deployer\Exception\RunException;

add('recipes', ['common']);

// Name of current user who is running deploy.
// If not set will try automatically get git user name,
// otherwise output of `whoami` command.
set('user', function () {
    if (getenv('CI') !== false) {
        return 'ci';
    }

    try {
        return runLocally('git config --get user.name');
    } catch (RunException $exception) {
        try {
            return runLocally('whoami');
        } catch (RunException $exception) {
            return 'no_user';
        }
    }
});

// Number of releases to preserve in releases folder.
set('keep_releases', 10);

// Repository to deploy.
set('repository', '');

// Default timeout for `run()` and `runLocally()` functions.
//
// Set to `null` to disable timeout.
set('default_timeout', 300);

/**
 * Remote environment variables.
 * ```php
 * set('env', [
 *     'KEY' => 'something',
 * ]);
 * ```
 *
 * It is possible to override it per `run()` call.
 *
 * ```php
 * run('echo $KEY', env: ['KEY' => 'over']);
 * ```
 */
set('env', []);

/**
 * Path to `.env` file which will be used as environment variables for each command per `run()`.
 *
 * ```php
 * set('dotenv', '{{current_path}}/.env');
 * ```
 */
set('dotenv', false);

/**
 * The deploy path.
 *
 * For example can be set for a bunch of host once as:
 * ```php
 * set('deploy_path', '~/{{alias}}');
 * ```
 */
set('deploy_path', function () {
    throw new ConfigurationException('Please, specify `deploy_path`.');
});

/**
 * Return current release path. Default to {{deploy_path}}/`current`.
 * ```php
 * set('current_path', '/var/public_html');
 * ```
 */
set('current_path', '{{deploy_path}}/current');

// Path to the `php` bin.
set('bin/php', function () {
    if (currentHost()->hasOwn('php_version')) {
        return '/usr/bin/php{{php_version}}';
    }
    return which('php');
});

// Path to the `git` bin.
set('bin/git', function () {
    return which('git');
});

// Should {{bin/symlink}} use `--relative` option or not. Will detect
// automatically.
set('use_relative_symlink', function () {
    return commandSupportsOption('ln', '--relative');
});

// Path to the `ln` bin. With predefined options `-nfs`.
set('bin/symlink', function () {
    return get('use_relative_symlink') ? 'ln -nfs --relative' : 'ln -nfs';
});

// Path to a file which will store temp script with sudo password.
// Defaults to `.dep/sudo_pass`. This script is only temporary and will be deleted after
// sudo command executed.
set('sudo_askpass', function () {
    if (test('[ -d {{deploy_path}}/.dep ]')) {
        return '{{deploy_path}}/.dep/sudo_pass';
    } else {
        return '/tmp/dep_sudo_pass';
    }
});

desc('Prepares a new release');
task('deploy:prepare', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
]);

desc('Publishes the release');
task('deploy:publish', [
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
]);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:publish',
]);


/**
 * Prints success message
 */
task('deploy:success', function () {
    info('successfully deployed!');
})
    ->hidden();


/**
 * Hook on deploy failure.
 */
task('deploy:failed', function () {
})
    ->hidden();

fail('deploy', 'deploy:failed');

/**
 * Follows latest application logs.
 */
desc('Shows application logs');
task('logs:app', function () {
    if (!has('log_files')) {
        warning("Please, specify \"log_files\" option.");
        return;
    }
    cd('{{current_path}}');
    run('tail -f {{log_files}}');
})->verbose();
