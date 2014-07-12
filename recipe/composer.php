<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';

/**
 * Main task
 */
task('deploy', [
    'deploy:start',
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
    'deploy:end'
])->desc('Deploy your project');

/**
 * Success message
 */
after('deploy', function () {
    $host = config()->getHost();
    writeln("<info>Successfully deployed on</info> <fg=cyan>$host</fg=cyan>");
});


