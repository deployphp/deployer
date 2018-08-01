<?php
/* (c) Jad Bitar <jadb@cakephp.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * CakePHP 3 Project Template configuration
 */

// CakePHP 3 Project Template shared dirs
set('shared_dirs', [
    'logs',
    'tmp',
]);

// CakePHP 3 Project Template shared files
set('shared_files', [
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
    run('{{release_path}}/bin/cake orm_cache clear');
    run('{{release_path}}/bin/cake orm_cache build');
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
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:init',
    'deploy:run_migrations',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
