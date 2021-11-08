<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['magento']);

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
desc('Clears cache');
task('deploy:cache:clear', function () {
    run("cd {{release_or_current_path}} && php -r \"require_once 'app/Mage.php'; umask(0); Mage::app()->cleanCache();\"");
});

/**
 * Remove files that can be used to compromise Magento
 */
task('deploy:clear_version', function () {
    run("rm -f {{release_or_current_path}}/LICENSE.html");
    run("rm -f {{release_or_current_path}}/LICENSE.txt");
    run("rm -f {{release_or_current_path}}/LICENSE_AFL.txt");
    run("rm -f {{release_or_current_path}}/RELEASE_NOTES.txt");
})->hidden();

after('deploy:update_code', 'deploy:clear_version');


/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:cache:clear',
    'deploy:publish',
]);
