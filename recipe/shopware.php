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
 * configure host:
 * host('SSH-HOSTNAME')
 *     ->set('remote_user', 'SSH-USER')
 *     ->set('deploy_path', '/var/www/shopware') // This is the path, where deployer will create its directory structure
 *     ->set('http_user', 'www-data') // Not needed, if the `user` is the same user, the webserver is running with
 *     ->set('http_group', 'www-data')
 *     ->set('writable_mode', 'chmod')
 *     ->set('writable_recursive', true)
 *     ->set('become', 'www-data'); // You might want to change user to execute remote tasks because of access rights of created cache files
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
    '.env.local',
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

task('sw:scheduled-task:register', function () {
    run('cd {{release_path}} && {{bin/console}} scheduled-task:register');
});

task('sw:theme:refresh', function () {
    run('cd {{release_path}} && {{bin/console}} theme:refresh');
});

// This task is not used per default, but can be used, e.g. in combination with `SHOPWARE_SKIP_THEME_COMPILE=1`,
// to build the theme remotely instead of locally.
task('sw:theme:compile', function () {
    run('cd {{release_path}} && {{bin/console}} theme:compile');
});

function getPlugins(): array
{
    $output = run('cd {{release_path}} && {{bin/console}} plugin:list --json');
    $plugins = json_decode($output);

    return $plugins;
}

task('sw:plugin:update:all', static function () {
    $plugins = getPlugins();
    foreach ($plugins as $plugin) {
        if ($plugin->installedAt && $plugin->upgradeVersion) {
            writeln("<info>Running plugin update for " . $plugin->name . "</info>\n");
            run("cd {{release_path}} && {{bin/console}} plugin:update " . $plugin->name);
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
    'sw:scheduled-task:register',
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
