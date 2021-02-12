<?php
namespace Deployer;
​
require 'recipe/common.php';
​
function parsePluginList($raw) {
    $lengths = array_filter(array_map('strlen', explode(' ', $raw[4])));
    $raw = array_slice($raw, 7, -3);
    
    $result = [];
    
    foreach ($raw as $row) {
        $pluginParts = [];
        foreach ($lengths as $length) {
            $pluginParts[] = trim(substr($row, 0, $length));
            $row = substr($row, $length + 1);
        }
        
        $result[] = [
            'plugin'            => $pluginParts[0] ?? null,
            'label'             => $pluginParts[1] ?? null,
            'version'           => $pluginParts[2] ?? null,
            'upgrade_version'   => $pluginParts[3] ?? null,
            'author'            => $pluginParts[4] ?? null,
            'installed'         => $pluginParts[5] ?? null,
            'active'            => $pluginParts[6] ?? null,
            'upgradeable'       => $pluginParts[7] ?? null
        ];
    }
    
    return $result;
}

set('git_tty', true);
set('allow_anonymous_stats', false);
set('default_timeout', 3600);
​
// Hosts
//inventory('hosts.yml');
​
//=============================
set('shared_files', [
    '.env',
    'public/.htaccess',
    'public/sw-domain-hash.html',
]);
​
set('shared_dirs', [
    'config/jwt',
    'config/secrets',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);
​
set('writable_dirs', [
    'custom/plugins',
    'config/jwt',
    'files',
    'var',
    'public/media',
    'public/thumbnail',
    'public/sitemap',
]);
set('writable_dirs_recursive', true);
​
set('static_folders', []);
​
//======================
task('sw:vendors', function () {
    run('cd {{release_path}} && composer install --no-interaction --optimize-autoloader --no-suggest ');
});
​
/*
 * For local deployment may be need to set correctly permissions
 */
/*
task('sw:writable', function () {
    run('cd {{release_path}} && sudo chown -R {{cmd_owner}}:{{cmd_group}} ./*');
});
task('sw:writable:dirs', function () {
    run('cd {{release_path}} && sudo chown -R {{cmd_owner}}:{{cmd_group}} var/');
    run('cd {{release_path}} && sudo chown -R {{cmd_owner}}:{{cmd_group}} public/media/ public/thumbnail/ public/sitemap/');
    run('cd {{release_path}} && sudo chmod -R 775 public/media/ public/thumbnail/ public/sitemap/');
    run('cd {{release_path}} && sudo chmod -R 775 var/');
});
task('sw:jwt', function () {
    run('cd {{release_path}} && sudo chown -R {{cmd_owner}}:{{cmd_group}} config/jwt/');
    run('cd {{release_path}} && sudo chmod 660 config/jwt/public.pem');
    run('cd {{release_path}} && sudo chmod 660 config/jwt/private.pem');
});
*/

//=======================
task('sw:build', function(){
    run('cd {{release_path}} && bin/build-js.sh;');
});
task('sw:database:migrate', static function () {
    run('cd {{release_path}} && bin/console database:migrate --all');
});
//************

task('sw:plugins', static function () {
    run('cd {{release_path}} && bin/console plugin:refresh;');
    
    $plugins = parsePluginList( explode("\n", run('cd {{release_path}} && bin/console plugin:list;')) );
    
    foreach ($plugins as $plugin) {
        if ($plugin['installed'] === 'No' || $plugin['active'] === 'No') {
            run("cd {{release_path}} && bin/console plugin:install --activate ".$plugin['plugin']);
        }
    }
});

task('sw:plugins:migrate', static function(){
    $plugins = parsePluginList( explode("\n", run('cd {{release_path}} && bin/console plugin:list;')) );
    
    foreach ($plugins as $plugin) {
        if ($plugin['installed'] === 'Yes' || $plugin['active'] === 'Yes') {
            run("cd {{release_path}} && bin/console database:migrate --all ".$plugin['plugin']." || true");
        }
    }
});

task('sw:plugins:handle', [
    'sw:plugins',
    'sw:plugins:migrate'
]);

//************

task('sw:theme:compile', function(){
    run('cd {{release_path}} && bin/console theme:compile;');
});


task('sw:sitemap:generate', function(){
    run('cd {{release_path}} && bin/console sitemap:generate;');
});
task('sw:cache:clear', function(){
    run('cd {{release_path}} && bin/console cache:clear;');
});
task('sw:cache:warmup', function(){
    run('cd {{release_path}} && bin/console cache:warmup;');
    run('cd {{release_path}} && bin/console http:cache:warm:up');
});
task('sw:assets:install', function(){
    run('cd {{release_path}} && bin/console assets:install;');
});

task('sw:init', [
    'sw:build',
    'sw:database:migrate',
    'sw:plugins:handle',
    'sw:theme:compile'
]);

task('sw:deploy', [
    //'sw:sitemap:generate', // Will be failed, if elasticsearch server is not configured. 
    'sw:cache:clear',
    'sw:cache:warmup',
    'sw:assets:install',
]);
//=======================

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    
    'deploy:shared',
    'deploy:writable',
    
    //'sw:writable', // For local deployment, uncomment if needed
    'sw:vendors',
    //'sw:jwt', // For local deployment, uncomment if needed
    
    'sw:init',
    'sw:deploy',
    //'sw:writable:dirs', // For local deployment, uncomment if needed
    'sw:cache:clear', // after all commands
    
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
])->desc('Deploy Project');

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

