<?php
namespace Deployer;

set('repository', 'git@github.com:shopware/production.git');

set('shared_files', [
    '.env'
]);
set('shared_dirs', [
    'var',
    'public/media',
    'public/thumbnail'
]);
set('writable_dirs', [
    'var',
    'public/media',
    'public/thumbnail'
]);
set('static_folders', []);
set('writable_dirs', [
    'public'
]);

task('sw:update_code', function(){
    run('git clone {{repository}} {{release_path}};');
});
task('sw:deploy','
    cd {{release_path}};
    composer install;
    ');

task('sw:theme:compile', function(){
    run('cd {{release_path}} && bin/console theme:compile;');
});
task('sw:cache:clear', function(){
    run('cd {{release_path}} && bin/console cache:clear;');
});
/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'sw:deploy',
    'deploy:shared',
    'deploy:writable',
    'sw:theme:compile',
    'sw:cache:clear',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
])->desc('Deploy your project');

after('deploy', 'success');
after('deploy:failed', 'deploy:unlock');
