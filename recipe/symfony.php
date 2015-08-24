<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

/**
 * Symfony Configuration
 */

// Symfony shared dirs
set('shared_dirs', ['app/logs']);

// Symfony shared files
set('shared_files', ['app/config/parameters.yml']);

// Symfony writable dirs
set('writable_dirs', ['app/cache', 'app/logs']);

// Assets
set('assets', ['web/css', 'web/images', 'web/js']);

// Environment vars
env('env_vars', 'SYMFONY_ENV=prod');
env('env', 'prod');

// Adding support for the Symfony3 directory structure
set('bin_dir', 'app');
set('var_dir', 'app');


/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    env('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    run(sprintf('if [ -d "{{cache_dir}}" ]; then %s rm -rf {{cache_dir}}; fi', useSudo()));

    // Create cache dir
    run(sprintf('%s mkdir -p {{cache_dir}}', useSudo()));

    // Set rights
    run(sprintf('%s chmod -R g+w {{cache_dir}}', useSudo()));
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return '{{release_path}}/'.$asset;
    }, get('assets')));

    $time = date('Ymdhi.s');

    run(sprintf('find %s -exec %s touch -t %s {} \';\' &> /dev/null || true', $assets, useSudo(), $time));
})->desc('Normalize asset timestamps');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {
    run(sprintf('%s php {{release_path}}/%s/console assetic:dump --env={{env}} --no-debug', useSudo(), trim(get('bin_dir'), '/')));
})->desc('Dump assets');


/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    run(sprintf('%s php {{release_path}}/%s/console cache:warmup  --env={{env}} --no-debug', useSudo(), trim(get('bin_dir'), '/')));
})->desc('Warm up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {
    run(sprintf('%s php {{release_path}}/%s/console doctrine:migrations:migrate --env={{env}} --no-debug --no-interaction', useSudo(), trim(get('bin_dir'), '/')));
})->desc('Migrate database');


/**
 * Remove app_dev.php files
 */
task('deploy:clear_controllers', function () {
    run(sprintf('%s rm -f {{release_path}}/web/app_*.php', useSudo()));
    run(sprintf('%s rm -f {{release_path}}/web/config.php', useSudo()));
})->setPrivate();
after('deploy:update_code', 'deploy:clear_controllers');


/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:writable',
    'deploy:assets',
    'deploy:vendors',
    'deploy:assetic:dump',
    'deploy:cache:warmup',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');
after('deploy', 'success');
