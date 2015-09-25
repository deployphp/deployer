<?php

/* (c) Jad Bitar <jadb@cakephp.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

/**
 * CakePHP 3 Project Template configuration
 */

// Yii 2 Advanced Project Template shared dirs
set('shared_dirs', [
    'logs',
    'tmp',
]);

// Yii 2 Advanced Project Template shared files
set('shared_files', [
    'config/app.php',
]);

/**
 * Initialization
 */
task('deploy:init', function () {
    run('{{release_path}}/bin/cake orm_cache clear');
    run('{{release_path}}/bin/cake orm_cache build');
})->desc('Initialization');

/**
 * Run migrations
 */
task('deploy:run_migrations', function () {
    run('{{release_path}}/bin/cake migrations migrate');
})->desc('Run migrations');

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:init',
    'deploy:run_migrations',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
