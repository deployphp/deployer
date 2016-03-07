<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

// Yii shared dirs
set('shared_dirs', ['runtime']);

// Yii writable dirs
set('writable_dirs', ['runtime']);

/**
 * Main task
 */
taskGroup('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
