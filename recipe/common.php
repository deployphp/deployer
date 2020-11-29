<?php
namespace Deployer;

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
require __DIR__ . '/deploy/status.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/update_code.php';
require __DIR__ . '/deploy/vendors.php';
require __DIR__ . '/deploy/writable.php';

use Deployer\Exception\RunException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;

add('recipes', ['common']);

// Name of current user who is running deploy.
// It will be shown in `dep status` command as author.
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
set('keep_releases', 5);

// Repository to deploy.
set('repository', '');

// List of dirs what will be shared between releases.
// Each release will have symlink to those dirs stored in {{deploy_path}}/shared dir.
// ```php
// set('shared_dirs', ['storage']);
// ```
set('shared_dirs', []);

// List of files what will be shared between releases.
// Each release will have symlink to those files stored in {{deploy_path}}/shared dir.
// ```php
// set('shared_files', ['.env']);
// ```
set('shared_files', []);

// List of dirs to copy between releases.
// For example you can copy `node_modules` to speedup npm install.
set('copy_dirs', []);

// List of paths to remove from {{release_path}}.
set('clear_paths', []);

// Use sudo for deploy:clear_path task?
set('clear_use_sudo', false);

set('use_relative_symlink', function () {
    return commandSupportsOption('ln', '--relative');
});
set('use_atomic_symlink', function () {
    return commandSupportsOption('mv', '--no-target-directory');
});

// Default timeout for `run()` and `runLocally()` functions. Default to 300 seconds.
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
 * run('echo $KEY', ['env' => ['KEY' => 'over']]
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
 * Return current release path. Default to {{deploy_path}}/`current`.
 * ```php
 * set('current_path', '/var/public_html');
 * ```
 */
set('current_path', '{{deploy_path}}/current');

// Custom php bin of remote host.
set('bin/php', function () {
    return locateBinaryPath('php');
});

// Custom git bin of remote host.
set('bin/git', function () {
    return locateBinaryPath('git');
});

// Custom ln bin of remote host.
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

/**
 * Default options
 */
option('tag', null, InputOption::VALUE_REQUIRED, 'Tag to deploy');
option('revision', null, InputOption::VALUE_REQUIRED, 'Revision to deploy');
option('branch', null, InputOption::VALUE_REQUIRED, 'Branch to deploy');

task('deploy:prepare', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
]);

task('deploy:publish', [
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
]);

/**
 * Prints success message
 */
task('deploy:success', function () {
    info('successfully deployed!');
})
    ->shallow()
    ->hidden();


/**
 * Hook on deploy failure.
 */
task('deploy:failed', function () {
})->hidden();

fail('deploy', 'deploy:failed');

/**
 * Follow latest application logs.
 */
desc('Follow latest application logs.');
task('logs', function () {
    if (!has('log_files')) {
        warning("Please, specify \"log_files\" option.");
        return;
    }

    if (output()->getVerbosity() === Output::VERBOSITY_NORMAL) {
        output()->setVerbosity(Output::VERBOSITY_VERBOSE);
    }
    cd('{{current_path}}');
    run('tail -f {{log_files}}');
});
