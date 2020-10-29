<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['cakephp']);

/**
 * CakePHP 4 Project Template configuration
 */

// CakePHP 4 Project Template shared dirs
set('shared_dirs', [
    'logs',
    'tmp',
]);

// CakePHP 4 Project Template shared files
set('shared_files', [
    'config/.env',
    'config/app.php',
]);

/**
 * Create plugins' symlinks
 */
task('deploy:init', function () {
    run('{{release_path}}/bin/cake plugin assets symlink');
})->desc('Initialization');

/**
 * Run migrations
 */
task('deploy:run_migrations', function () {
    run('{{release_path}}/bin/cake migrations migrate');
    run('{{release_path}}/bin/cake schema_cache clear');
    run('{{release_path}}/bin/cake schema_cachebuild');
})->desc('Run migrations');

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:init',
    'deploy:run_migrations',
    'deploy:publish',
])->desc('Deploy your project');
