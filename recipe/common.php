<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require __DIR__ . '/deploy/check_remote.php';
require __DIR__ . '/deploy/cleanup.php';
require __DIR__ . '/deploy/clear_paths.php';
require __DIR__ . '/deploy/copy_dirs.php';
require __DIR__ . '/deploy/info.php';
require __DIR__ . '/deploy/lock.php';
require __DIR__ . '/deploy/release.php';
require __DIR__ . '/deploy/rollback.php';
require __DIR__ . '/deploy/setup.php';
require __DIR__ . '/deploy/shared.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/update_code.php';
require __DIR__ . '/deploy/vendors.php';
require __DIR__ . '/deploy/writable.php';

use Deployer\Exception\RunException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;

/**
 * Facts
 */

set('hostname', function () {
    return currentHost()->getHostname();
});

set('remote_user', function () {
    return currentHost()->getRemoteUser();
});

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

set('writable_dirs', []);
set('writable_mode', 'acl'); // chmod, chown, chgrp or acl.
set('writable_use_sudo', false); // Using sudo in writable commands?
set('writable_recursive', true); // Common for all modes
set('writable_chmod_mode', '0755'); // For chmod mode
set('writable_chmod_recursive', true); // For chmod mode only (if is boolean, it has priority over `writable_recursive`)

set('http_user', false);
set('http_group', false);

set('clear_paths', []);         // Relative path from release_path
set('clear_use_sudo', false);    // Using sudo in clean commands?

set('cleanup_use_sudo', false); // Using sudo in cleanup commands?

set('use_relative_symlink', function () {
    return commandSupportsOption('ln', '--relative');
});
set('use_atomic_symlink', function () {
    return commandSupportsOption('mv', '--no-target-directory');
});

set('composer_action', 'install');
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --no-suggest');

set('env', []); // Run command environment (for example, SYMFONY_ENV=prod)

/**
 * Return current release path.
 */
set('current_path', function () {
    $link = run("readlink {{deploy_path}}/current");
    return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
});


/**
 * Custom bins
 */
set('bin/php', function () {
    return locateBinaryPath('php');
});

set('bin/git', function () {
    return locateBinaryPath('git');
});

set('bin/composer', function () {
    if (commandExist('composer')) {
        $composer = '{{bin/php}} ' . locateBinaryPath('composer');
    }

    if (empty($composer)) {
        run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}");
        $composer = '{{bin/php}} {{release_path}}/composer.phar';
    }

    return $composer;
});

set('bin/symlink', function () {
    return get('use_relative_symlink') ? 'ln -nfs --relative' : 'ln -nfs';
});

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
    info(currentHost()->getTag() . ' successfully deployed!');
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
    cd('{{deploy_path}}/current');
    run('tail -f {{log_files}}');
});
