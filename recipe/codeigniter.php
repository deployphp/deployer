<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Functions;

require_once __DIR__ . '/common.php';

// CodeIgniter shared dirs
Functions\set('shared_dirs', ['application/cache', 'application/logs']);

// CodeIgniter writable dirs
Functions\set('writable_dirs', ['application/cache', 'application/logs']);

/**
 * Main task
 */
Functions\task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

Functions\after('deploy', 'success');
