<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/common.php';
// This recipe supports Laravel 5.1+,
// for older versions, please read the documentation https://github.com/deployphp/docs

// Laravel shared dirs
set('shared_dirs', ['storage']);

// Laravel 5 shared file
set('shared_files', ['.env']);

// Laravel writable dirs
set('writable_dirs', ['bootstrap/cache', 'storage']);

/**
 * Helper tasks
 */
task('artisan:up', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan up');
    writeln('<info>'.$output.'</info>');
})->desc('Disable maintenance mode');

task('artisan:down', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan down');
    writeln('<error>'.$output.'</error>');
})->desc('Enable maintenance mode');

task('artisan:migrate', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate --force');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan migrate');

task('artisan:migrate:rollback', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate:rollback --force');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan migrate:rollback');

task('artisan:migrate:status', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan migrate:status');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan migrate:status');

task('artisan:db:seed', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan db:seed --force');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan db:seed');

task('artisan:cache:clear', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan cache:clear');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan cache:clear');

task('artisan:config:cache', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan config:cache');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan config:cache');

task('artisan:route:cache', function () {
    $output = run('{{bin/php}} {{deploy_path}}/current/artisan route:cache');
    writeln('<info>' . $output . '</info>');
})->desc('Execute artisan route:cache');

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
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'cleanup',
    'artisan:cache:clear',
    'artisan:config:cache',
])->desc('Deploy your project');

after('deploy', 'success');
