<?php
namespace Deployer;

set('repository', 'git@github.com:shopware/production.git');

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
    'var/log',
    'public/media',
    'public/thumbnail',
    'public/sitemap'
]);
set('static_folders', []);
set('writable_dirs', [
    'public'
]);

task('sw:update_code', function() {
    run('git clone {{repository}} {{release_path}};');
});
task('sw:deploy','
    cd {{release_path}};
    composer install;
');

task('assets:install', function() {
    run('cd {{release_path}} && bin/console assets:install;');
});

task('theme:compile', function() {
    run('cd {{release_path}} && bin/console theme:compile;');
});

task('cache:clear', function() {
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
    'assets:install',
    'theme:compile',
    'cache:clear',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
])->desc('Deploy your project');

after('deploy', 'success');
after('deploy:failed', 'deploy:unlock');
