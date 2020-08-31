<?php
namespace Deployer;

set('repository', 'git@github.com:shopware/production.git');

set('release_name', function () {
    return date('YmdHis');
});

set('shared_files', [
    '.env'
]);
set('shared_dirs', [
    'custom/plugins',
    'config/jwt',
    'files',
    'var/log',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);
set('writable_dirs', [
    'custom/plugins',
    'config/jwt',
    'files',
    'var',
    'public/media',
    'public/thumbnail',
    'public/sitemap'
]);
set('static_folders', []);

task('sw:update_code', function(){
    run('git clone {{repository}} {{release_path}}');
});
task('sw:system:install', function(){
    run('cd {{release_path}} && bin/console system:install');
});
task('sw:build', function(){
    run('cd {{release_path}}/bin && sh build.sh');
});
task('sw:system:setup', function(){
    run('cd {{release_path}} && bin/console system:setup');
});
task('sw:theme:compile', function(){
    run('cd {{release_path}} && bin/console theme:compile');
});
task('sw:cache:clear', function(){
    run('cd {{release_path}} && bin/console cache:clear');
});
task('sw:cache:warmup', function(){
    run('cd {{release_path}} && bin/console cache:warmup');
    run('cd {{release_path}} && bin/console http:cache:warm:up');
});
task('sw:database:migrate', function(){
    run('cd {{release_path}} && bin/console database:migrate --all');
});

/**
 * Grouped SW deploy tasks
 */
task('sw:deploy',[
    'sw:build',
    'sw:database:migrate',
    'sw:theme:compile',
    'sw:cache:clear'
]);

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'sw:deploy',
    'deploy:writable',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'sw:cache:warmup',
    'cleanup',
    'success'
])->desc('Deploy your project');

after('deploy', 'success');
after('deploy:failed', 'deploy:unlock');
