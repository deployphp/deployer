<?php
/**
 * ## Usage
 *
 * Add {{repository}} to your _deploy.php_ file:
 *
 * ```php
 * set('repository', 'git@github.com:shopware/production.git');
 * ```
 *
 * :::note
 * Please remember that the installation must be modified so that it can be
 * [build without database](https://developer.shopware.com/docs/guides/hosting/installation-updates/deployments/build-w-o-db#compiling-the-storefront-without-database).
 * :::
 */
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['shopware']);

set('bin/console', '{{bin/php}} {{release_or_current_path}}/bin/console');

set('default_timeout', 3600); // Increase when tasks take longer than that.

// These files are shared among all releases.
set('shared_files', [
    '.env',
    'install.lock',
    'public/.htaccess',
    'public/.user.ini',
]);

// These directories are shared among all releases.
set('shared_dirs', [
    'config/jwt',
    'files',
    'var/log',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);

// These directories are made writable (the definition of "writable" requires attention).
// Please note that the files in `config/jwt/*` receive special attention in the `sw:writable:jwt` task.
set('writable_dirs', [
    'config/jwt',
    'custom/plugins',
    'files',
    'public/bundles',
    'public/css',
    'public/fonts',
    'public/js',
    'public/media',
    'public/sitemap',
    'public/theme',
    'public/thumbnail',
    'var',
]);

// This task remotely executes the `cache:clear` console command on the target server.
task('sw:cache:clear', static function () {
    run('cd {{release_path}} && {{bin/console}} cache:clear --no-warmup');
});

// This task remotely executes the cache warmup console commands on the target server, so that the first user, who
// visits the website, doesn't have to wait for the cache to be built up.
task('sw:cache:warmup', static function () {
    run('cd {{release_path}} && {{bin/console}} cache:warmup');
    run('cd {{release_path}} && {{bin/console}} http:cache:warm:up');
});

// This task remotely executes the `database:migrate` console command on the target server.
task('sw:database:migrate', static function () {
    run('cd {{release_path}} && {{bin/console}} database:migrate --all');
});

task('sw:plugin:refresh', function () {
    run('cd {{release_path}} && {{bin/console}} plugin:refresh');
});

task('sw:theme:refresh', function () {
    run('cd {{release_path}} && {{bin/console}} theme:refresh');
});

function getPlugins(): array
{
    $output = explode("\n", run('cd {{release_path}} && {{bin/console}} plugin:list'));

    // Take line over headlines and count "-" to get the size of the cells.
    $lengths = array_filter(array_map('strlen', explode(' ', $output[4])));
    $splitRow = function ($row) use ($lengths) {
        $columns = [];
        foreach ($lengths as $length) {
            $columns[] = trim(substr($row, 0, $length));
            $row = substr($row, $length + 1);
        }
        return $columns;
    };
    $headers = $splitRow($output[5]);
    $splitRowIntoStructure = function ($row) use ($splitRow, $headers) {
        $columns = $splitRow($row);
        return array_combine($headers, $columns);
    };

    // Ignore first seven lines (headline, title, table, ...).
    $rows = array_slice($output, 7, -3);

    $plugins = [];
    foreach ($rows as $row) {
        $pluginInformation = $splitRowIntoStructure($row);
        $plugins[] = $pluginInformation;
    }

    return $plugins;
}

task('sw:plugin:update:all', static function () {
    $plugins = getPlugins();
    foreach ($plugins as $plugin) {
        if ($plugin['Installed'] === 'Yes') {
            writeln("<info>Running plugin update for " . $plugin['Plugin'] . "</info>\n");
            run("cd {{release_path}} && {{bin/console}} plugin:update " . $plugin['Plugin']);
        }
    }
});

task('sw:writable:jwt', static function () {
    run('cd {{release_path}} && chmod -R 660 config/jwt/*');
});

/**
 * Grouped SW deploy tasks.
 */
task('sw:deploy', [
    'sw:database:migrate',
    'sw:plugin:refresh',
    'sw:theme:refresh',
    'sw:cache:clear',
    'sw:plugin:update:all',
    'sw:cache:clear',
]);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'sw:deploy',
    'deploy:clear_paths',
    'sw:cache:warmup',
    'sw:writable:jwt',
    'deploy:publish',
]);


task('sw-build-without-db:get-remote-config', static function () {
    if (!test('[ -d {{current_path}} ]')) {
        return;
    }
    within('{{deploy_path}}/current', function () {
        run('{{bin/php}} ./bin/console bundle:dump');
        download('{{deploy_path}}/current/var/plugins.json', './var/');

        run('{{bin/php}} ./bin/console theme:dump');
        download('{{deploy_path}}/current/files/theme-config', './files/');
    });
});

task('sw-build-without-db:build', static function () {
    runLocally('CI=1 SHOPWARE_SKIP_BUNDLE_DUMP=1 ./bin/build-js.sh');
});

task('sw-build-without-db', [
    'sw-build-without-db:get-remote-config',
    'sw-build-without-db:build',
]);

before('deploy:update_code', 'sw-build-without-db');
