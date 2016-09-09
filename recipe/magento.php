<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
task('mage:cc', function () {
    run("cd {{deploy_path}}/current && {{bin/php}} -r \"require_once 'app/Mage.php'; umask(0); Mage::app()->cleanCache();\"");
})->desc('Clear cache');

/**
 * Clear Cache Static files
 */
task('mage:cc:static', function (){
    run("cd {{deploy_path}}/current && rm -rf media/css/*.css && rm -rf media/js/*.js && rm -rf media/css_secure/*.css");
})->desc('Clear cache images');

/**
 * Enter in Maintenance Mode Magento
 */
task('mage:disable', function (){
    run("cd {{deploy_path}}/current && touch maintenance.flag");
})->desc('Enable Maintenance Mode');

/**
 * Disable Maintenance Mode Magento
 */
task('mage:enable', function (){
    run("cd {{deploy_path}}/current && rm -rf maintenance.flag");
})->desc('Disable Maintenance Mode');

/**
 * Reindex all index of Magento
 */
task('mage:indexer',function(){
    run("cd {{deploy_path}}/current/shell && {{bin/php}} -f indexer.php -- reindexall");
})->desc('Run Indexer');

/**
 * Delete Locks of indexers
 */
task('mage:unlock',function(){
    run("cd {{deploy_path}}/current && rm -rf var/locks/*");
})->desc('Delete locks');

/**
 * Clean Magento Logs
 */
task('mage:clean_log',function(){
    run("cd {{deploy_path}}/current/shell && {{bin/php}} -f log.php -- clean");
})->desc('Clean Log');


/**
 * Remove files that can be used to compromise Magento
 */
task('deploy:clear_version', function () {
    run("rm -f {{deploy_path}}/current/LICENSE.html");
    run("rm -f {{deploy_path}}/current/LICENSE.txt");
    run("rm -f {{deploy_path}}/current/LICENSE_AFL.txt");
    run("rm -f {{deploy_path}}/current/RELEASE_NOTES.txt");
    /**
     * Enterprise Support
     */
    run("rm -f {{deploy_path}}/current/LICENSE_EE.html");
    run("rm -f {{deploy_path}}/current/LICENSE_EE.txt");
})->setPrivate();

after('deploy:update_code', 'deploy:clear_version');

before('mage:indexer','mage:unlock');


/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'mage:cc',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
