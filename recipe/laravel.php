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

task('deploy:migrate', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate --force');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan migrate');

task('deploy:migrate_rollback', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate:rollback --force');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan migrate:rollback');

task('deploy:migrate_status', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate:status');
    writeln('<info>' . $output . '</info>');
})->desc('Show status of migration');

/**
 * Task deploy:public_disk support the public disk.
 * To run this task automatically, please add below line to your deploy.php file
 * <code>after('deploy:symlink', 'deploy:public_disk');</code>
 * @see https://laravel.com/docs/5.2/filesystem#configuration
 */
task('deploy:public_disk', function () {
    // Remove from source.
    run('if [ -d $(echo {{release_path}}/public/storage) ]; then rm -rf {{release_path}}/public/storage; fi');

    // Create shared dir if it does not exist.
    run('mkdir -p {{deploy_path}}/shared/storage/app/public');

    // Symlink shared dir to release dir
    run('ln -nfs {{deploy_path}}/shared/storage/app/public {{release_path}}/public/storage');
})->desc('Make symlink for public disk');

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
