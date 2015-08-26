<?php

/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Functions;

require_once __DIR__ . '/common.php';

/**
 * Magento Configuration
 */

// Magento shared dirs
Functions\set('shared_dirs', ['var', 'media']);

// Magento shared files
Functions\set('shared_files', ['app/etc/local.xml']);

// Magento writable dirs
Functions\set('writable_dirs', ['var', 'media']);

/**
 * Clear cache
 */
Functions\task('deploy:cache:clear', function () {
    Functions\run("cd {{release_path}} && php -r \"require_once 'app/Mage.php'; umask(0); Mage::app()->cleanCache();\"");
})->desc('Clear cache');

/**
 * Remove files that can be used to compromise Magento
 */
Functions\task('deploy:clear_version', function () {
    Functions\run("rm -f {{release_path}}/LICENSE.html");
    Functions\run("rm -f {{release_path}}/LICENSE.txt");
    Functions\run("rm -f {{release_path}}/LICENSE_AFL.txt");
    Functions\run("rm -f {{release_path}}/RELEASE_NOTES.txt");
})->setPrivate();

Functions\after('deploy:update_code', 'deploy:clear_version');


/**
 * Main task
 */
Functions\task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:cache:clear',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

Functions\after('deploy', 'success');
