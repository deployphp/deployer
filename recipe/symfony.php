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

// Auto migrate
set('auto_migrate', false);

// Environment vars
env('env_vars', 'SYMFONY_ENV=prod');
env('env', 'prod');


/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    env('cache_dir', '{{release_path}}/app/cache');

    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    run('mkdir -p {{cache_dir}}');

    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));

    $time = date('Ymdhi.s');

    run("find $assets -exec touch -t $time {} ';' &> /dev/null || true");
})->desc('Normalize asset timestamps');


/**
 * Dump all assets to the filesystem
 */
task('deploy:assetic:dump', function () {

    run("php {{release_path}}/app/console assetic:dump --env={{env}} --no-debug");

})->desc('Dump assets');


/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {

    run('php {{release_path}}/app/console cache:warmup  --env={{env}} --no-debug');

})->desc('Warm up cache');


/**
 * Migrate database
 */
task('database:migrate', function () {

    run("php {{release_path}}/app/console doctrine:migrations:migrate --env={{env}} --no-debug --no-interaction");

})->desc('Migrate database');


/**
 * Remove app_dev.php files
 */
task('deploy:clear_controllers', function () {

    run("rm -f {{release_path}}/web/app_*.php");

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
