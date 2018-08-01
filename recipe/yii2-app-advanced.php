<?php
/* (c) Alexey Rogachev <arogachev90@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Yii 2 Advanced Project Template configuration
 */

// Yii 2 Advanced Project Template shared dirs
set('shared_dirs', [
    'frontend/runtime',
    'backend/runtime',
    'console/runtime',
]);

// Yii 2 Advanced Project Template shared files
set('shared_files', [
    'common/config/main-local.php',
    'common/config/params-local.php',
    'frontend/config/main-local.php',
    'frontend/config/params-local.php',
    'backend/config/main-local.php',
    'backend/config/params-local.php',
    'console/config/main-local.php',
    'console/config/params-local.php',
]);

/**
 * Initialization
 */
task('deploy:init', function () {
    run('{{bin/php}} {{release_path}}/init --env=Production --overwrite=n');
})->desc('Initialization');

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
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:init',
    'deploy:shared',
    'deploy:writable',
    'deploy:run_migrations',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
