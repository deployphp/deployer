<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require __DIR__ . '/deploy/config.php';
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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Configuration
 */

set('keep_releases', 5);

set('repository', ''); // Repository to deploy.
set('branch', ''); // Branch to deploy.

set('shared_dirs', []);
set('shared_files', []);

set('copy_dirs', []);

set('writable_dirs', []);
set('writable_mode', 'acl'); // chmod, chown, chgrp or acl.
set('writable_use_sudo', false); // Using sudo in writable commands?
set('writable_chmod_mode', '0755'); // For chmod mode

set('http_user', false);
set('http_group', false);

set('clear_paths', []);         // Relative path from deploy_path
set('clear_use_sudo', false);    // Using sudo in clean commands?

set('use_relative_symlink', true);

set('composer_action', 'install');
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

set('env_vars', ''); // Variable assignment before cmds (for example, SYMFONY_ENV={{set}})

set('git_cache', function () { //whether to use git cache - faster cloning by borrowing objects from existing clones.
    $gitVersion = run('{{bin/git}} version');
    $regs       = [];
    if (preg_match('/((\d+\.?)+)/', $gitVersion, $regs)) {
        $version = $regs[1];
    } else {
        $version = "1.0.0";
    }
    return version_compare($version, '2.3', '>=');
});


/**
 * Return current release path.
 */
set('current_path', function () {
    $link = run("readlink {{deploy_path}}/current")->toString();
    return substr($link, 0, 1) === '/' ? $link : get('deploy_path') . '/' . $link;
});


/**
 * Custom bins.
 */
set('bin/php', function () {
    return run('which php')->toString();
});

set('bin/git', function () {
    return run('which git')->toString();
});

set('bin/composer', function () {
    if (commandExist('composer')) {
        $composer = run('which composer')->toString();
    }

    if (empty($composer)) {
        run("cd {{release_path}} && curl -sS https://getcomposer.org/installer | {{bin/php}}");
        $composer = '{{bin/php}} {{release_path}}/composer.phar';
    }

    return $composer;
});

set('bin/symlink', function () {
    if (get('use_relative_symlink')) {
        // Check if target system supports relative symlink.
        if (run('if [[ "$(man ln)" =~ "--relative" ]]; then echo "true"; fi')->toBool()) {
            return 'ln -nfs --relative';
        }
    }
    return 'ln -nfs';
});

/**
 * Default arguments and options.
 */
argument('stage', InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers');
option('tag', null, InputOption::VALUE_OPTIONAL, 'Tag to deploy');
option('revision', null, InputOption::VALUE_OPTIONAL, 'Revision to deploy');
option('branch', null, InputOption::VALUE_OPTIONAL, 'Branch to deploy');

/**
 * Tasks
 */
desc('Show current release');
task('current', function () {
    writeln('Current release: ' . basename(get('current_path')));
});


/**
 * Success message
 */
task('success', function () {
    Deployer::setDefault('terminate_message', '<info>Successfully deployed!</info>');
})->once()->setPrivate();


/**
 * Deploy failure
 */
task('deploy:failed', function () {
})->setPrivate();
onFailure('deploy', 'deploy:failed');
