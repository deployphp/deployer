<?php
namespace Deployer;

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../contrib/cachetool.php';


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

// Magento directory relative to repository root. Use "." (default) if it is not located in a subdirectory
set('magento_dir', '.');


set('shared_files', [
    '{{magento_dir}}/app/etc/env.php',
    '{{magento_dir}}/var/.maintenance.ip',
]);
set('shared_dirs', [
    '{{magento_dir}}/var/composer_home',
    '{{magento_dir}}/var/log',
    '{{magento_dir}}/var/export',
    '{{magento_dir}}/var/report',
    '{{magento_dir}}/var/import',
    '{{magento_dir}}/var/import_history',
    '{{magento_dir}}/var/session',
    '{{magento_dir}}/var/importexport',
    '{{magento_dir}}/var/backups',
    '{{magento_dir}}/var/tmp',
    '{{magento_dir}}/pub/sitemap',
    '{{magento_dir}}/pub/media',
    '{{magento_dir}}/pub/static/_cache'
]);
set('writable_dirs', [
    '{{magento_dir}}/var',
    '{{magento_dir}}/pub/static',
    '{{magento_dir}}/pub/media',
    '{{magento_dir}}/generated',
    '{{magento_dir}}/var/page_cache'
]);
set('clear_paths', [
    '{{magento_dir}}/generated/*',
    '{{magento_dir}}/pub/static/_cache/*',
    '{{magento_dir}}/var/generation/*',
    '{{magento_dir}}/var/cache/*',
    '{{magento_dir}}/var/page_cache/*',
    '{{magento_dir}}/var/view_preprocessed/*'
]);

set('bin/magento', '{{release_or_current_path}}/{{magento_dir}}/bin/magento');

set('magento_version', function () {
    // detect version
    $versionOutput = run('{{bin/php}} {{bin/magento}} --version');
    preg_match('/(\d+\.?)+(-p\d+)?$/', $versionOutput, $matches);
    return $matches[0] ?? '2.0';
});

set('maintenance_mode_status_active', function () {
    // detect maintenance mode active
    $maintenanceModeStatusOutput = run("{{bin/php}} {{bin/magento}} maintenance:status");
    return strpos($maintenanceModeStatusOutput, MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG) !== false;
});

// Deploy without setting maintenance mode if possible
set('enable_zerodowntime', true);

// Tasks

// To work correctly with artifact deployment, it is necessary to set the MAGE_MODE correctly in `app/etc/config.php`
// e.g.
// ```php
// 'MAGE_MODE' => 'production'
// ```
desc('Compiles magento di');
task('magento:compile', function () {
    run("{{bin/php}} {{bin/magento}} setup:di:compile");
    run('cd {{release_or_current_path}}/{{magento_dir}} && {{bin/composer}} dump-autoload -o');
});

// To work correctly with artifact deployment it is necessary to set `system/dev/js` , `system/dev/css` and `system/dev/template`
// in `app/etc/config.php`, e.g.:
// ```php
// 'system' => [
//     'default' => [
//         'dev' => [
//             'js' => [
//                 'merge_files' => '1',
//                 'minify_files' => '1'
//             ],
//             'css' => [
//                 'merge_files' => '1',
//                 'minify_files' => '1'
//             ],
//             'template' => [
//                 'minify_html' => '1'
//             ]
//         ]
//     ]
// ```
desc('Deploys assets');
task('magento:deploy:assets', function () {

    $themesToCompile = '';
    if (count(get('magento_themes')) > 0) {
        foreach (get('magento_themes') as $theme) {
            $themesToCompile .= ' -t ' . $theme;
        }
    }

    run("{{bin/php}} {{bin/magento}} setup:static-content:deploy --content-version={{content_version}} {{static_content_locales}} $themesToCompile -j {{static_content_jobs}}");
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
    // do not use {{bin/magento}} because it would be in "release" but the maintenance mode must be set in "current"
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/{{magento_dir}}/bin/magento maintenance:enable; fi");
});

desc('Disables maintenance mode');
task('magento:maintenance:disable', function () {
    // do not use {{bin/magento}} because it would be in "release" but the maintenance mode must be set in "current"
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/{{magento_dir}}/bin/magento maintenance:disable; fi");
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
            run('{{bin/php}} {{bin/magento}} app:config:status');
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

        run('{{bin/php}} {{bin/magento}} app:config:import --no-interaction');

        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:disable');
        }
    }
});

desc('Upgrades magento database');
task('magento:upgrade:db', function () {
    $databaseUpgradeNeeded = false;

    try {
        run('{{bin/php}} {{bin/magento}} setup:db:status');
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

        run("{{bin/php}} {{bin/magento}} setup:upgrade --keep-generated --no-interaction");

        if (get('enable_zerodowntime') && !get('maintenance_mode_status_active')) {
            invoke('magento:maintenance:disable');
        }
    }
});

desc('Flushes Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{bin/magento}} cache:flush");
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

// Artifact deployment section

// The file the artifact is saved to
set('artifact_file', 'artifact.tar.gz');

// The directory the artifact is saved in
set('artifact_dir', 'artifacts');

// Points to a file with a list of files to exclude from packaging.
// The format is as with the `tar --exclude-from=[file]` option
set('artifact_excludes_file', 'artifacts/excludes');

// If set to true, the artifact is built from a clean copy of the project repository instead of the current working directory
set('build_from_repo', false);

// Set this value if "build_from_repo" is set to true. The target to deploy must also be set with "--branch", "--tag" or "--revision"
set('repository', null);

// The relative path to the artifact file. If the directory does not exist, it will be created
set('artifact_path', function () {
    if (!testLocally('[ -d {{artifact_dir}} ]')) {
        runLocally('mkdir -p {{artifact_dir}}');
    }
    return get('artifact_dir') . '/' . get('artifact_file');
});

// The location of the tar command. On MacOS you should have installed gtar, as it supports the required settings
set('bin/tar', function () {
    if (commandExist('gtar')) {
        return which('gtar');
    } else {
        return which('tar');
    }
});

// tasks section

desc('Packages all relevant files in an artifact.');
task('artifact:package', function() {
    if (!test('[ -f {{artifact_excludes_file}} ]')) {
        throw new GracefulShutdownException(
            "No artifact excludes file provided, provide one at artifacts/excludes or change location"
        );
    }
    run('{{bin/tar}} --exclude-from={{artifact_excludes_file}} -czf {{artifact_path}} {{release_or_current_path}}');
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

desc('Clears generated files prior to building.');
task('build:remove-generated', function() {
    run('rm -rf generated/*');
});

desc('Prepare local artifact build');
task('build:prepare', function() {
    if (!currentHost()->get('local')) {
        throw new GracefulShutdownException('Artifact can only be built locally, you provided a non local host');
    }

    $buildDir = get('build_from_repo') ? get('artifact_dir') . '/repo' : '.';
    set('deploy_path', $buildDir);
    set('release_path', $buildDir);
    set('current_path', $buildDir);

    if (!get('build_from_repo')) {
        return;
    }

    $repository = (string) get('repository');
    if ($repository === '') {
        throw new GracefulShutdownException('You must specify the "repository" option.');
    }

    run('rm -rf {{release_or_current_path}}');
    run('git clone {{repository}} {{release_or_current_path}}');
    run('git -C {{release_or_current_path}} checkout --force {{target}}');
});

desc('Builds an artifact.');
task('artifact:build', [
        'build:prepare',
        'build:remove-generated',
        'deploy:vendors',
        'magento:compile',
        'magento:deploy:assets',
        'artifact:package',
]);

// Array of shared files that will be added to the default shared_files without overriding
set('additional_shared_files', []);
// Array of shared directories that will be added to the default shared_dirs without overriding
set('additional_shared_dirs', []);


desc('Adds additional files and dirs to the list of shared files and dirs');
task('deploy:additional-shared', function () {
    add('shared_files', get('additional_shared_files'));
    add('shared_dirs', get('additional_shared_dirs'));
});


desc('Prepares an artifact on the target server');
task('artifact:prepare', [
        'deploy:info',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'artifact:upload',
        'artifact:extract',
        'deploy:additional-shared',
        'deploy:shared',
        'deploy:writable',
]);

desc('Executes the tasks after artifact is released');
task('artifact:finish', [
        'magento:cache:flush',
        'cachetool:clear:opcache',
        'deploy:cleanup',
        'deploy:unlock',
]);

desc('Actually releases the artifact deployment');
task('artifact:deploy', [
        'artifact:prepare',
        'magento:upgrade:db',
        'magento:config:import',
        'deploy:symlink',
        'artifact:finish',
]);

fail('artifact:deploy', 'deploy:failed');
