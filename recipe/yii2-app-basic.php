<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Yii 2 Basic Project Template configuration
 */

// Yii 2 Basic Project Template shared dirs
set('shared_dirs', ['runtime']);

/**
 * Run migrations
 */
task('deploy:run_migrations', function () {
    run('{{bin/php}} {{release_path}}/yii migrate up --interactive=0');
})->desc('Run migrations');

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:run_migrations',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
