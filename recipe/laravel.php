<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';
// This recipe support Laravel 5.1+, with orther version, please see document https://github.com/deployphp/docs

// Laravel shared dirs
set('shared_dirs', [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Laravel 5 shared file
set('shared_files', ['.env']);

// Laravel writable dirs
set('writable_dirs', ['bootstrap/cache', 'storage']);

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

after('deploy', 'success');

/**
 * Helper tasks
 */
task('deploy:up', function () {
    $output = run('php {{deploy_path}}/current/artisan up');
    writeln('<info>'.$output.'</info>');
})->desc('Disable maintenance mode');

task('deploy:down', function () {
    $output = run('php {{deploy_path}}/current/artisan down');
    writeln('<error>'.$output.'</error>');
})->desc('Enable maintenance mode');
