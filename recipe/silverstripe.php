<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['silverstripe']);

/**
 * Silverstripe configuration
 */

set('shared_assets', function () {
    if (test('[ -d {{release_or_current_path}}/public ]') || test('[ -d {{deploy_path}}/shared/public ]')) {
        return 'public/assets';
    }
    return 'assets';
});


// Silverstripe shared dirs
set('shared_dirs', [
    '{{shared_assets}}'
]);

// Silverstripe writable dirs
set('writable_dirs', [
    '{{shared_assets}}'
]);

// Silverstripe cli script
set('silverstripe_cli_script', function () {
    $paths = [
        'framework/cli-script.php',
        'vendor/silverstripe/framework/cli-script.php'
    ];
    foreach ($paths as $path) {
        if (test('[ -f {{release_or_current_path}}/'.$path.' ]')) {
            return $path;
        }
    }
});

/**
 * Helper tasks
 */
desc('Run /dev/build');
task('silverstripe:build', function () {
    return run('{{bin/php}} {{release_or_current_path}}/{{silverstripe_cli_script}} /dev/build');
});

desc('Run /dev/build?flush=all');
task('silverstripe:buildflush', function () {
    return run('{{bin/php}} {{release_or_current_path}}/{{silverstripe_cli_script}} /dev/build flush=all');
});

/**
 * Main task
 */
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'silverstripe:buildflush',
    'deploy:publish',
]);
