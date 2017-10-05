<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Magento Configuration
 */

// Magento shared dirs
set('shared_dirs', ['var', 'media']);

// Magento shared files
set('shared_files', ['app/etc/local.xml']);

// Magento writable dirs
set('writable_dirs', ['var', 'media']);

/**
 * Clear cache
 */
task('deploy:cache:clear', function () {
    run("cd {{release_path}} && php -r \"require_once 'app/Mage.php'; umask(0); Mage::app()->cleanCache();\"");
})->desc('Clear cache');

/**
 * Remove files that can be used to compromise Magento
 */
task('deploy:clear_version', function () {
    run("rm -f {{release_path}}/LICENSE.html");
    run("rm -f {{release_path}}/LICENSE.txt");
    run("rm -f {{release_path}}/LICENSE_AFL.txt");
    run("rm -f {{release_path}}/RELEASE_NOTES.txt");
})->setPrivate();

after('deploy:update_code', 'deploy:clear_version');


/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:cache:clear',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
