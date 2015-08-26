<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Functions;

require_once __DIR__ . '/common.php';

// Laravel shared dirs
Functions\set('shared_dirs', ['storage']);

// Laravel 5 shared file
Functions\set('shared_files', ['.env']);

// Laravel writable dirs
Functions\set('writable_dirs', ['storage', 'vendor']);

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
