<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

// Configuration
set('shared_files', ['app/etc/env.php', 'var/.maintenance.ip']);
set('shared_dirs', ['var/log', 'var/backups', 'pub/media']);
set('writable_dirs', ['var', 'pub/static', 'pub/media']);
set('clear_paths', ['var/generation/*', 'var/cache/*']);

// Tasks
desc('Enable all modules'); task('magento:enable', function () {
    run("{{bin/php}} {{release_path}}/bin/magento module:enable --all");
    writeln("Modules enabled");
});

desc('Compile magento di');
task('magento:compile', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:di:compile");
    writeln("DI compiled");
});

desc('Deploy assets');
task('magento:deploy:assets', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy");
    writeln("Static assets deployed");
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("{{bin/php}} {{deploy_path}}/current/bin/magento maintenance:enable");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("{{bin/php}} {{deploy_path}}/current/bin/magento maintenance:disable");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    run("{{bin/php}} {{release_path}}/bin/magento setup:db-schema:upgrade");
    run("{{bin/php}} {{release_path}}/bin/magento setup:db-data:upgrade");
    writeln("Database upgraded");
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{release_path}}/bin/magento cache:flush");
    writeln("Magento cache flushed");
});

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:enable',
    'magento:compile',
    'magento:deploy:assets',
    'magento:maintenance:enable',
    'magento:upgrade:db',
    'magento:cache:flush',
    'magento:maintenance:disable'
]);

after('deploy:clear_paths', 'deploy:magento');
