<?php
namespace Deployer;

/*
 * As Statamic is a Laravel Package, we will extend the Laravel
 * recipe and simply add Statamic specific commands.
 */
require_once __DIR__ . '/laravel.php';

add('recipes', ['statamic']);
add('writable_dirs', [
    'storage/statamic',
]);

set('statamic_version', function () {
    $result = run('{{bin/php}} {{release_or_current_path}}/please --version');
    preg_match_all('/(\d+\.?)+/', $result, $matches);
    return $matches[0][0] ?? 'unknown';
});

/*
 * Addons
 */

desc('Rebuild the cached addon package manifest');
task('statamic:addons:discover', artisan('statamic:addons:discover'));

/*
 * Assets
 */

desc('Generate asset preset manipulations');
task('statamic:assets:generate-presets', artisan('statamic:assets:generate-presets'));

desc('Generate asset metadata files');
task('statamic:assets:meta', artisan('statamic:assets:meta'));

/*
 * Git
 */

desc('Git add and commit tracked content');
task('statamic:git:commit', artisan('statamic:git:commit'));

/*
 * Glide
 */

desc('Clear the Glide image cache');
task('statamic:glide:clear', artisan('statamic:glide:clear'));

/*
 * Responsive Images (not in the core)
 */

desc('Generate responsive images');
task('statamic:responsive:generate', artisan('statamic:responsive:generate'));

desc('Regenerate responsive images');
task('statamic:responsive:regenerate', artisan('statamic:responsive:regenerate'));

/*
 * Search
 */

desc('Insert an item into its search indexes');
task('statamic:search:insert', artisan('statamic:search:insert'));

desc('Update a search index');
task('statamic:search:update', artisan('statamic:search:update'));

/*
 * Stache
 */

desc('Clear the "Stache" cache');
task('statamic:stache:clear', artisan('statamic:stache:clear'));

desc('Diagnose any problems with the Stache');
task('statamic:stache:doctor', artisan('statamic:stache:doctor'));

desc('Clear and rebuild the "Stache" cache');
task('statamic:stache:refresh', artisan('statamic:stache:refresh'));

desc('Build the "Stache" cache');
task('statamic:stache:warm', artisan('statamic:stache:warm'));

/*
 * Static
 */

desc('Clear the static page cache');
task('statamic:static:clear', artisan('statamic:static:clear'));

desc('Warms the static cache by visiting all URLs');
task('statamic:static:warm', artisan('statamic:static:warm'));

/*
 * Support
 */

desc('Outputs details helpful for support requests');
task('statamic:support:details', artisan('statamic:support:details'));

/*
 * Updated
 */

desc('Run update scripts from specific version');
task('statamic:updates:run', artisan('statamic:updates:run'));

/*
 * Main Deploy Script for Statamic, which
 * will overwrite the Laravel default.
 */

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:storage:link',
    'artisan:cache:clear',
    'statamic:stache:clear',
    'statamic:stache:warm',
    'deploy:publish',
]);
