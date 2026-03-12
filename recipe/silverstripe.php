<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['silverstripe']);

/**
 * Path to the assets folder.
 * Defaults to `"public/assets"` (or `"assets"` on older installations).
 */
set('shared_assets', function () {
    if (test('[ -d {{release_or_current_path}}/public ]') || test('[ -d {{deploy_path}}/shared/public ]')) {
        return 'public/assets';
    }
    return 'assets';
});


set('shared_dirs', [
    '{{shared_assets}}',
]);

set('writable_dirs', [
    '{{shared_assets}}',
]);

/**
 * Path to the `sake` binary.
 * Defaults to `"vendor/bin/sake"`, if it exists.
 */
set('silverstripe_sake', function () {
    $candidates = [
        'vendor/bin/sake',
        'vendor/silverstripe/framework/bin/sake',
    ];
    foreach ($candidates as $candidate) {
        if (test("[ -x '{{release_or_current_path}}/$candidate' ]")) {
            return $candidate;
        }
    }
});

/**
 * Deprecated option, retained for backward compatibility.
 * For Silverstripe 6 and above, use `silverstripe_sake` instead.
 */
set('silverstripe_cli_script', function () {
    $candidates = [
        'framework/cli-script.php',
        'vendor/silverstripe/framework/cli-script.php',
    ];
    foreach ($candidates as $candidate) {
        if (test("[ -f '{{release_or_current_path}}/$candidate' ]")) {
            return $candidate;
        }
    }
});

/**
 * Helper tasks
 */
desc('Rebuild the database');
task('silverstripe:build', function () {
    if (get('silverstripe_cli_script')) {
        // Old behavior (Silverstripe < 6)
        run('{{bin/php}} {{release_or_current_path}}/{{silverstripe_cli_script}} /dev/build');
    } elseif (get('silverstripe_sake')) {
        // New behavior (Silverstripe >= 6)
        run('{{release_or_current_path}}/{{silverstripe_sake}} db:build');
    }
});

desc('Rebuild database and cache');
task('silverstripe:buildflush', function () {
    if (get('silverstripe_cli_script')) {
        // Old behavior (Silverstripe < 6)
        run('{{bin/php}} {{release_or_current_path}}/{{silverstripe_cli_script}} /dev/build flush=all');
    } elseif (get('silverstripe_sake')) {
        // New behavior (Silverstripe >= 6)
        run('{{release_or_current_path}}/{{silverstripe_sake}} -f db:build');
    }
});

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'silverstripe:buildflush',
    'deploy:publish',
]);
