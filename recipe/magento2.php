<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

// Configuration
set('shared_files', [
    'app/etc/env.php',
    'var/.maintenance.ip',
]);
set('shared_dirs', [
    'var/composer_home',
    'var/log',
    'var/cache',
    'var/export',
    'var/report',
    'var/import_history',
    'var/session',
    'var/importexport',
    'var/backups',
    'var/tmp',
    'pub/sitemaps',
    'pub/media'
]);
set('writable_dirs', [
    'var',
    'pub/static',
    'pub/media',
    'generated'
]);
set('clear_paths', [
    'generated/*',
    'pub/static/_cache/*',
    'var/generation/*',
    'var/cache/*',
    'var/page_cache/*',
    'var/view_preprocessed/*'
]);

// Tasks
desc('Compile magento di');
task('magento:compile', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:di:compile");
    run('cd {{release_path}} && {{bin/composer}} dump-autoload -o');
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy");
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{deploy_path}}/current) ]; then {{bin/php}} {{deploy_path}}/current/bin/magento maintenance:enable; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{deploy_path}}/current) ]; then {{bin/php}} {{deploy_path}}/current/bin/magento maintenance:disable; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:upgrade --keep-generated");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{release_path}}/bin/magento cache:flush");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:compile',
    'magento:deploy:assets',
    'magento:maintenance:enable',
    'magento:upgrade:db',
    'magento:cache:flush',
    'magento:maintenance:disable'
]);

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:clear_paths',
    'deploy:magento',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

after('deploy:failed', 'magento:maintenance:disable');
