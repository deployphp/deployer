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

require __DIR__ . '/provision/provision.php';

use Deployer\Exception\RunException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;

/**
 * Facts
 */

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

/**
 * Configuration
 */

set('keep_releases', 5);

set('repository', ''); // Repository to deploy.

set('shared_dirs', []);
set('shared_files', []);

set('copy_dirs', []);

set('clear_paths', []);         // Relative path from release_path
set('clear_use_sudo', false);    // Using sudo in clean commands?

set('cleanup_use_sudo', false); // Using sudo in cleanup commands?

set('use_relative_symlink', function () {
    return commandSupportsOption('ln', '--relative');
});
set('use_atomic_symlink', function () {
    return commandSupportsOption('mv', '--no-target-directory');
});

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
 * Return current release path.
 */
set('current_path', '{{deploy_path}}/current');

/**
 * Custom bins
 */
set('bin/php', function () {
    return locateBinaryPath('php');
});

set('bin/git', function () {
    return locateBinaryPath('git');
});

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
 * Success message
 */
task('deploy:success', function () {
    info('successfully deployed!');
})
    ->shallow()
    ->hidden();


/**
 * Deploy failure
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
