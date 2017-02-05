<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_files', ['wp-config.php']);
set('shared_dirs', ['wp-content/uploads']);

/**
 * Chown files to correct web server user
 * for your OS, so uploads work.
 */
task('deploy:chown', function () {
    run('chown -R www-data:www-data ' . env('deploy_path'));
});

task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors', 
    'deploy:writable',
    'deploy:symlink',
    'deploy:chown',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
