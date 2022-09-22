<?php
namespace Deployer;

require_once __DIR__ . '/common.php';


use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Host\Host;

const CONFIG_IMPORT_NEEDED_EXIT_CODE = 2;
const DB_UPDATE_NEEDED_EXIT_CODE = 2;
const MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG = 'maintenance mode is active';

add('recipes', ['magento2']);

// Configuration

// By default setup:static-content:deploy uses `en_US`.
// To change that, simply put `set('static_content_locales', 'en_US de_DE');`
// in you deployer script.
set('static_content_locales', 'en_US');

// Configuration

// You can also set the themes to run against. By default it'll deploy
// all themes - `add('magento_themes', ['Magento/luma', 'Magento/backend']);`
set('magento_themes', [

]);

// Configuration

// Also set the number of conccurent jobs to run. The default is 1
// Update using: `set('static_content_jobs', '1');`
set('static_content_jobs', '1');

set('content_version', function () {
    return time();
});

set('shared_files', [
    'app/etc/env.php',
    'var/.maintenance.ip',
]);
set('shared_dirs', [
    'var/composer_home',
    'var/log',
    'var/export',
    'var/report',
    'var/import',
    'var/import_history',
    'var/session',
    'var/importexport',
    'var/backups',
    'var/tmp',
    'pub/sitemap',
    'pub/media'
]);
set('writable_dirs', [
    'var',
    'pub/static',
    'pub/media',
    'generated',
    'var/page_cache'
]);
set('clear_paths', [
    'generated/*',
    'pub/static/_cache/*',
    'var/generation/*',
    'var/cache/*',
    'var/page_cache/*',
    'var/view_preprocessed/*'
]);

set('magento_version', function () {
    // detect version
    $versionOutput = run('{{bin/php}} {{release_or_current_path}}/bin/magento --version');
    preg_match('/(\d+\.?)+(-p\d+)?$/', $versionOutput, $matches);
    return $matches[0] ?? '2.0';
});

set('maintenance_mode_status_active', function () {
    // detect maintenance mode active
    $maintenanceModeStatusOutput = run("{{bin/php}} {{release_or_current_path}}/bin/magento maintenance:status");
    return strpos($maintenanceModeStatusOutput, MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG) !== false;
});

// Deploy without setting maintenance mode if possible
set('enable_zerodowntime', true);

// Tasks
desc('Compiles magento di');
task('magento:compile', function () {
    run('cd {{release_or_current_path}} && {{bin/composer}} dump-autoload -o');
    run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:di:compile");
    run('cd {{release_or_current_path}} && {{bin/composer}} dump-autoload -o');
});

desc('Deploys assets');
task('magento:deploy:assets', function () {

    $themesToCompile = '';
    if (count(get('magento_themes')) > 0) {
        foreach (get('magento_themes') as $theme) {
            $themesToCompile .= ' -t ' . $theme;
        }
    }

    run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:static-content:deploy --content-version={{content_version}} {{static_content_locales}} $themesToCompile -j {{static_content_jobs}}");
});

desc('Syncs content version');
task('magento:sync:content_version', function () {
    $timestamp = time();
    on(select('all'), function (Host $host) use ($timestamp) {
        $host->set('content_version', $timestamp);
    });
})->once();

before('magento:deploy:assets', 'magento:sync:content_version');

desc('Enables maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/bin/magento maintenance:enable; fi");
});

desc('Disables maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/bin/magento maintenance:disable; fi");
});

desc('Config Import');
task('magento:config:import', function () {
    $configImportNeeded = false;

    if(version_compare(get('magento_version'), '2.2.0', '<')) {
        //app:config:import command does not exist in 2.0.x and 2.1.x branches
        $configImportNeeded = false;
    } elseif(version_compare(get('magento_version'), '2.2.4', '<')) {
        //app:config:status command does not exist until 2.2.4, so proceed with config:import in every deploy
        $configImportNeeded = true;
    } else {
        try {
            run('{{bin/php}} {{release_or_current_path}}/bin/magento app:config:status');
        } catch (RunException $e) {
            if ($e->getExitCode() == CONFIG_IMPORT_NEEDED_EXIT_CODE) {
                $configImportNeeded = true;
            } else {
                throw $e;
            }
        }
    }

    if ($configImportNeeded) {
        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:enable');
        }

        run('{{bin/php}} {{release_or_current_path}}/bin/magento app:config:import --no-interaction');

        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:disable');
        }
    }
});

desc('Upgrades magento database');
task('magento:upgrade:db', function () {
    $databaseUpgradeNeeded = false;

    try {
        run('{{bin/php}} {{release_or_current_path}}/bin/magento setup:db:status');
    } catch (RunException $e) {
        if ($e->getExitCode() == DB_UPDATE_NEEDED_EXIT_CODE) {
            $databaseUpgradeNeeded = true;
        } else {
            throw $e;
        }
    }

    if ($databaseUpgradeNeeded) {
        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:enable');
        }

        run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:upgrade --keep-generated --no-interaction");

        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:disable');
        }
    }
});

desc('Flushes Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{release_or_current_path}}/bin/magento cache:flush");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:build',
    'magento:config:import',
    'magento:upgrade:db',
    'magento:cache:flush',
]);

desc('Magento2 build operations');
task('magento:build', [
    'magento:compile',
    'magento:deploy:assets',
]);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:magento',
    'deploy:publish',
]);

after('deploy:failed', 'magento:maintenance:disable');

// artifact deployment section
// settings section
set('artifact_file', 'artifact.tar.gz');
set('artifact_dir', 'artifacts');
set('artifact_excludes_file', 'artifacts/excludes');

set('artifact_path', function () {
    if (!test('[ -d {{artifact_dir}} ]')) {
        run('mkdir {{artifact_dir}}');
    }
    return get('artifact_dir') . '/' . get('artifact_file');
});

set('bin/tar', function () {
    if (commandExist('gtar')) {
        return which('gtar');
    } else {
        return which('tar');
    }
});

set('cacheToolPath', function() {
    return get('cacheTool', '{{current_path}}/bin/cachetool');
});

// tasks section
desc('Packages all relevant files in an artifact.');
task('artifact:package', function() {
    if (!test('[ -f {{artifact_excludes_file}} ]')) {
        throw new GracefulShutdownException(
            "No artifact excludes file provided, provide one at artivacts/excludes or change location"
        );
    }
    run('{{bin/tar}} --exclude-from={{artifact_excludes_file}} -czf {{artifact_path}} .');
});

desc('Uploads artifact in release folder for extraction.');
task('artifact:upload', function () {
    upload(get('artifact_path'), '{{release_path}}');
});

desc('Extracts artifact in release path.');
task('artifact:extract', function () {
    run('{{bin/tar}} -xzpf {{release_path}}/{{artifact_file}} -C {{release_path}}');
    run('rm -rf {{release_path}}/{{artifact_file}}');
});

desc('Provides env.php for build.');
task('build:prepare-env', function() {
    $deployEnv = get('deploy_env','app/etc/deploy.php');
    if (!test('[ -f ./'.$deployEnv.' ]')) {
        throw new GracefulShutdownException(
            "No deploy env provided, provide one at app/etc/deploy.php or change location"
        );
    }
    run ('cp '.$deployEnv.' app/etc/env.php');
});

desc('Clears generated files prior to building.');
task('build:remove-generated', function() {
    run('rm -rf generated/*');
});

desc('Clears the opcache, cache tool required.');
task('cache:clear:opcache', function() {
    if ($fpmSocket = get('fpm_socket', '')) {
        run('{{bin/php}} -f {{cacheToolPath}} opcache:reset --fcgi '.$fpmSocket);
    }
});

desc('Builds an artifact.');
task('artifact:build', function () {
    if(currentHost()->get('local')) {
        set('deploy_path', '.');
        set('release_path', '.');
        set('current_path', '.');
        invoke('build:prepare-env');
        invoke('build:remove-generated');
        invoke('deploy:vendors');
        invoke('magento:compile');
        invoke('magento:deploy:assets');
        invoke('artifact:package');
    } else {
        throw new GracefulShutdownException("Artifact can only be built locally, you provided a non local host");
    }
});

desc('Prepares an artifact on the target server');
task('artifact:prepare', function(){
    if(currentHost()->get('local')) {
        throw new GracefulShutdownException("You can only deploy to a non localhost");
    } else {
        add('shared_files', get('additional_shared_files') ?? []);
        add('shared_dirs', get('additional_shared_dirs') ?? []);
        invoke('deploy:info');
        invoke('deploy:setup');
        invoke('deploy:lock');
        invoke('deploy:release');
        invoke('artifact:upload');
        invoke('artifact:extract');
        invoke('deploy:shared');
        invoke('deploy:writable');
    }
});

desc('Executes the tasks after artifact is released');
task('artifact:finish', function() {
    if(currentHost()->get('local')) {
        throw new GracefulShutdownException("You can only deploy to a non localhost");
    } else {
        invoke('magento:cache:flush');
        invoke('cache:clear:opcache');
        invoke('deploy:cleanup');
        invoke('deploy:unlock');
    }
});

desc('Actually releases the artifact deployment');
task('artifact:deploy', function()  {

    if(currentHost()->get('local')) {
        throw new GracefulShutdownException("You can only deploy to a non localhost");
    } else {
        invoke('artifact:prepare');

        invoke('magento:upgrade:db');
        invoke('magento:config:import');
        invoke('deploy:symlink');

        invoke('artifact:finish');
    }
});

