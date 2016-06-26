<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

// FuelPHP 1.x shared dirs
set('shared_dirs', [
    'fuel/app/cache', 'fuel/app/logs',
]);

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

after('deploy:prepare', 'deploy:acquire_lock');
after('deploy:symlink', 'deploy:release_lock');
after('deploy', 'success');
