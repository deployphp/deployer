<?php

namespace Deployer;

use MJS\TopSort\Implementations\FixedArraySort;

require_once __DIR__ . '/common.php';

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
    'files',
    'var',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);
set('static_folders', []);

task('sw:update_code', static function () {
    run('git clone {{repository}} {{release_or_current_path}}');
});
task('sw:system:install', static function () {
    run('cd {{release_or_current_path}} && bin/console system:install');
});
task('sw:build', static function () {
    run('cd {{release_or_current_path}}/bin && bash build.sh');
});
task('sw:system:setup', static function () {
    run('cd {{release_or_current_path}} && bin/console system:setup');
});
task('sw:theme:compile', static function () {
    run('cd {{release_or_current_path}} && bin/console theme:compile');
});
task('sw:cache:clear', static function () {
    run('cd {{release_or_current_path}} && bin/console cache:clear');
});
task('sw:cache:warmup', static function () {
    run('cd {{release_or_current_path}} && bin/console cache:warmup');
    run('cd {{release_or_current_path}} && bin/console http:cache:warm:up');
});
task('sw:database:migrate', static function () {
    run('cd {{release_or_current_path}} && bin/console database:migrate --all');
});
task('sw:plugin:refresh', function () {
    run('cd {{release_or_current_path}} && bin/console plugin:refresh');
});

/**
 * @return array
 * @throws \MJS\TopSort\CircularDependencyException
 * @throws \MJS\TopSort\ElementNotFoundException
 */
function getSortedPlugins(): array
{
    cd('{{release_or_current_path}}');
    $plugins = explode("\n", run('bin/console plugin:list'));

    // take line over headlines and count "-" to get the size of the cells
    $lengths = array_filter(array_map('strlen', explode(' ', $plugins[4])));

    // ignore first seven lines (headline, title, table, ...)
    $plugins = array_slice($plugins, 7, -3);
    $parsedPlugins = [];
    foreach ($plugins as $plugin) {
        $pluginParts = [];
        foreach ($lengths as $length) {
            $pluginParts[] = trim(substr($plugin, 0, $length));
            $plugin = substr($plugin, $length + 1);
        }
        $parsedPlugins[$pluginParts[0]] = $pluginParts;
    }

    $composer = json_decode(run('cat composer.lock'), true);

    $pluginMapping = $dependencies = [];
    foreach ($parsedPlugins as $plugin) {
        $pluginName = $plugin[0];
        // collect cpmposer plugin names
        foreach ($composer['packages'] as $config) {
            if (!isset($config['extra']['shopware-plugin-class'])) {
                // we only collect a mapping for shopware modules name <-> composer name
                continue;
            }
            if (str_ends_with($config['extra']['shopware-plugin-class'], $pluginName)) {
                $pluginMapping[$config['name']] = $pluginName;
            }
        }

        // collect dependencies
        foreach ($composer['packages'] as $config) {
            if (!isset($pluginMapping[$config['name']])) {
                // if the composer.json doesn't belong to a shopware module
                // or doesn't have dependencies, ignore it
                continue;
            }
            $dependencies[$config['name']] = array_filter(array_keys($config['require'] ?? []),
                static function ($composerName) use ($pluginMapping) {
                    // only add dependencies between shopware modules
                    return isset($pluginMapping[$composerName]);
                });
        }
    }

    $sorter = new FixedArraySort();
    foreach ($dependencies as $name => $dep) {
        $sorter->add($name, $dep);
    }

    return array_map(static function ($name) use ($parsedPlugins, $pluginMapping) {
        return $parsedPlugins[$pluginMapping[$name]];
    }, $sorter->sort());
}

task('sw:plugin:activate:all', static function () {
    invoke('sw:plugin:refresh');

    foreach (getSortedPlugins() as $pluginInfo) {
        [
            $plugin,
            $label,
            $version,
            $upgrade,
            $author,
            $installed,
            $active,
            $upgradeable,
        ] = $pluginInfo;

        if ($installed === 'No' || $active === 'No') {
            run("cd {{release_or_current_path}} && bin/console plugin:install --activate $plugin");
        }
    }
});

task('sw:plugin:migrate:all', static function () {
    invoke('sw:plugin:refresh');
    foreach (getSortedPlugins() as $pluginInfo) {
        [
            $plugin,
            $label,
            $version,
            $upgrade,
            $author,
            $installed,
            $active,
            $upgradeable,
        ] = $pluginInfo;

        if ($installed === 'Yes' || $active === 'Yes') {
            run("cd {{release_or_current_path}} && bin/console database:migrate --all $plugin || true");
        }
    }
});

task('sw:plugin:upgrade:all', static function () {
    invoke('sw:plugin:refresh');
    foreach (getSortedPlugins() as $pluginInfo) {
        [
            $plugin,
            $label,
            $version,
            $upgrade,
            $author,
            $installed,
            $active,
            $upgradeable,
        ] = $pluginInfo;

        if ($upgradeable === 'Yes') {
            run("cd {{release_or_current_path}} && bin/console plugin:update $plugin");
        }
    }
});

/**
 * Grouped SW deploy tasks
 */
task('sw:deploy', [
    'sw:plugin:activate:all',
    'sw:database:migrate',
    'sw:plugin:migrate:all',
    'sw:build',
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
