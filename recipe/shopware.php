<?php
namespace Deployer;

add('recipes', ['shopware']);

set('repository', 'git@github.com:shopware/production.git');

set('release_name', static function () {
    return date('YmdHis');
});

set('shared_files', [
    '.env',
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
    'public/sitemap',
]);
set('static_folders', []);

task('sw:update_code', static function () {
    run('git clone {{repository}} {{release_path}}');
});
task('sw:system:install', static function () {
    run('cd {{release_path}} && bin/console system:install');
});
task('sw:build', static function () {
    run('cd {{release_path}}/bin && bash build.sh');
});
task('sw:system:setup', static function () {
    run('cd {{release_path}} && bin/console system:setup');
});
task('sw:theme:compile', static function () {
    run('cd {{release_path}} && bin/console theme:compile');
});
task('sw:cache:clear', static function () {
    run('cd {{release_path}} && bin/console cache:clear');
});
task('sw:cache:warmup', static function () {
    run('cd {{release_path}} && bin/console cache:warmup');
    run('cd {{release_path}} && bin/console http:cache:warm:up');
});
task('sw:database:migrate', static function () {
    run('cd {{release_path}} && bin/console database:migrate --all');
});
task('sw:plugin:refresh', function (){
    run('cd {{release_path}} && bin/console plugin:refresh');
});
task('sw:plugin:activate:all', static function () {
    task('sw:plugin:refresh');
    $plugins = explode("\n", run('cd {{release_path}} && bin/console plugin:list'));

    // take line over headlines and count "-" to get the size of the cells
    $lengths = array_filter(array_map('strlen', explode(' ', $plugins[4])));

    // ignore first seven lines (headline, title, table, ...)
    $plugins = array_slice($plugins, 7, -3);
    foreach ($plugins as $plugin) {
        $pluginParts = [];
        foreach ($lengths as $length) {
            $pluginParts[] = trim(substr($plugin, 0, $length));
            $plugin = substr($plugin, $length + 1);
        }

        [
            $plugin,
            $label,
            $version,
            $upgrade,
            $version,
            $author,
            $installed,
            $active,
            $upgradeable,
        ] = $pluginParts;

        if ($installed === 'No' || $active === 'No') {
            run("cd {{release_path}} && bin/console plugin:install --activate $plugin");
        }
    }
});
task('sw:plugin:migrate:all', static function(){
    $plugins = explode("\n", run('cd {{release_path}} && bin/console plugin:list'));

    // take line over headlines and count "-" to get the size of the cells
    $lengths = array_filter(array_map('strlen', explode(' ', $plugins[4])));

    // ignore first seven lines (headline, title, table, ...)
    $plugins = array_slice($plugins, 7, -3);
    foreach ($plugins as $plugin) {
        $pluginParts = [];
        foreach ($lengths as $length) {
            $pluginParts[] = trim(substr($plugin, 0, $length));
            $plugin = substr($plugin, $length + 1);
        }

        [
            $plugin,
            $label,
            $version,
            $upgrade,
            $version,
            $author,
            $installed,
            $active,
            $upgradeable,
        ] = $pluginParts;

        if ($installed === 'Yes' || $active === 'Yes') {
            run("cd {{release_path}} && bin/console database:migrate --all $plugin || true");
        }
    }
});

/**
 * Grouped SW deploy tasks
 */
task('sw:deploy', [
    'sw:build',
    'sw:plugin:activate:all',
    'sw:database:migrate',
    'sw:plugin:migrate:all',
    'sw:theme:compile',
    'sw:cache:clear',
]);

/**
 * Main task
 */
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'sw:deploy',
    'deploy:clear_paths',
    'sw:cache:warmup',
    'deploy:publish',
]);
