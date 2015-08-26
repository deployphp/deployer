<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

use Deployer\Functions;

/**
 * Symfony Configuration
 */

// Symfony shared dirs
Functions\set('shared_dirs', ['app/logs']);

// Symfony shared files
Functions\set('shared_files', ['app/config/parameters.yml']);

// Symfony writable dirs
Functions\set('writable_dirs', ['app/cache', 'app/logs']);

// Assets
Functions\set('assets', ['web/css', 'web/images', 'web/js']);

// Environment vars
Functions\env('env_vars', 'SYMFONY_ENV=prod');
Functions\env('env', 'prod');

// Adding support for the Symfony3 directory structure
Functions\set('bin_dir', 'app');
Functions\set('var_dir', 'app');


/**
 * Create cache dir
 */
Functions\task('deploy:create_cache_dir', function () {
    // Set cache dir
    Functions\env('cache_dir', '{{release_path}}/' . trim(Functions\get('var_dir'), '/') . '/cache');

    // Remove cache dir if it exist
    Functions\run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');

    // Create cache dir
    Functions\run('mkdir -p {{cache_dir}}');

    // Set rights
    Functions\run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');


/**
 * Normalize asset timestamps
 */
Functions\task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, Functions\get('assets')));

    $time = date('Ymdhi.s');

    Functions\run("find $assets -exec touch -t $time {} ';' &> /dev/null || true");
})->desc('Normalize asset timestamps');


/**
 * Dump all assets to the filesystem
 */
Functions\task('deploy:assetic:dump', function () {

    Functions\run('php {{release_path}}/' . trim(Functions\get('bin_dir'), '/') . '/console assetic:dump --env={{env}} --no-debug');

})->desc('Dump assets');


/**
 * Warm up cache
 */
Functions\task('deploy:cache:warmup', function () {

    Functions\run('php {{release_path}}/' . trim(Functions\get('bin_dir'), '/') . '/console cache:warmup  --env={{env}} --no-debug');

})->desc('Warm up cache');


/**
 * Migrate database
 */
Functions\task('database:migrate', function () {

    Functions\run('php {{release_path}}/' . trim(Functions\get('bin_dir'), '/') . '/console doctrine:migrations:migrate --env={{env}} --no-debug --no-interaction');

})->desc('Migrate database');


/**
 * Remove app_dev.php files
 */
Functions\task('deploy:clear_controllers', function () {

    Functions\run("rm -f {{release_path}}/web/app_*.php");
    Functions\run("rm -f {{release_path}}/web/config.php");

})->setPrivate();

Functions\after('deploy:update_code', 'deploy:clear_controllers');


/**
 * Main task
 */
Functions\task('deploy', [
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

Functions\after('deploy', 'success');
