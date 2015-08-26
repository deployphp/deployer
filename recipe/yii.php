<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

use Deployer\Functions;

// Yii shared dirs
Functions\set('shared_dirs', ['runtime']);

// Yii writable dirs
Functions\set('writable_dirs', ['runtime']);

/**
 * Main task
 */
Functions\task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

Functions\after('deploy', 'success');
