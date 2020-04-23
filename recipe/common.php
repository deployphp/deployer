<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require __DIR__ . '/config/current.php';
require __DIR__ . '/config/dump.php';
require __DIR__ . '/config/hosts.php';
require __DIR__ . '/deploy/info.php';
require __DIR__ . '/deploy/prepare.php';
require __DIR__ . '/deploy/lock.php';
require __DIR__ . '/deploy/release.php';
require __DIR__ . '/deploy/update_code.php';
require __DIR__ . '/deploy/clear_paths.php';
require __DIR__ . '/deploy/shared.php';
require __DIR__ . '/deploy/writable.php';
require __DIR__ . '/deploy/vendors.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/cleanup.php';
require __DIR__ . '/deploy/copy_dirs.php';
require __DIR__ . '/deploy/rollback.php';

use Deployer\Task\Context;
use Symfony\Component\Console\Input\InputOption;

/**
 * Facts
 */

set('hostname', function () {
    return Context::get()->getHost()->getHostname();
});

set('user', function () {
    try {
        return runLocally('git config --get user.name');
    } catch (\Throwable $exception) {
        if (false !== getenv('CI')) {
            return 'Continuous Integration';
        }

        return 'no_user';
    }
});

set('target', function () {
    return input()->getArgument('stage') ?: get('hostname');
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
        $composer = locateBinaryPath('composer');
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


/**
 * Default options
 */
option('tag', null, InputOption::VALUE_REQUIRED, 'Tag to deploy');
option('revision', null, InputOption::VALUE_REQUIRED, 'Revision to deploy');
option('branch', null, InputOption::VALUE_REQUIRED, 'Branch to deploy');


/**
 * Success message
 */
task('success', function () {
    writeln('<info>Successfully deployed!</info>');
})
    ->local()
    ->shallow()
    ->setPrivate();


/**
 * Deploy failure
 */
task('deploy:failed', function () {
})->setPrivate();

fail('deploy', 'deploy:failed');
