<?php
namespace Deployer;

set('repository', 'git@github.com:shopware/production.git');

set('shared_files', [
    '.env'
]);
set('shared_dirs', [
    'config/jwt',
    'config/secrets',
    'public/media',
    'public/thumbnail',
    'public/sitemap'
]);
set('writable_dirs', [
    'var',
    'public/media',
    'public/thumbnail'
]);
set('static_folders', []);

task('sw:update_code', function(){
    run('git clone {{repository}} {{release_path}};');
});

task('sw:system:install', function(){
    run('cd {{release_path}} && bin/console system:install;');
});
task('sw:storefront:build', function(){
    run('cd {{release_path}} && bin/build.sh;');
});
task('sw:system:setup', function(){
    run('cd {{release_path}} && bin/console system:setup;');
});
task('sw:theme:compile', function(){
    run('cd {{release_path}} && bin/console theme:compile;');
});
task('sw:cache:clear', function(){
    run('cd {{release_path}} && bin/console cache:clear;');
});
task('sw:assets:install', function(){
    run('cd {{release_path}} && bin/console assets:install;');
});

task('sw:deploy',[
    'sw:storefront:build',
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
    'cleanup',
    'success'
])->desc('Deploy your project');

after('deploy', 'success');
after('deploy:failed', 'deploy:unlock');
