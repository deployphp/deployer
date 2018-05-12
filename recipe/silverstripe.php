<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Silverstripe configuration
 */

// Silverstripe shared dirs
set('shared_dirs', [
    'assets'
]);

// Silverstripe writable dirs
set('writable_dirs', ['assets']);

// Silverstripe cli script
set('silverstripe_cli_script', function () {
    $paths = [
        'framework/cli-script.php',
        'vendor/silverstripe/framework/cli-script.php'
    ];
    foreach ($paths as $path) {
        if (test('[ -f {{release_path}}/'.$path.' ]')) {
            return $path;
        }
    }
});

/**
 * Helper tasks
 */
task('silverstripe:build', function () {
    return run('{{bin/php}} {{release_path}}/{{silverstripe_cli_script}} /dev/build');
})->desc('Run /dev/build');

task('silverstripe:buildflush', function () {
    return run('{{bin/php}} {{release_path}}/{{silverstripe_cli_script}} /dev/build flush=all');
})->desc('Run /dev/build?flush=all');

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'silverstripe:buildflush',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
