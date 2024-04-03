<?php
namespace Deployer;

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../contrib/cachetool.php';

use Deployer\Exception\ConfigurationException;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Deployer\Host\Host;

const CONFIG_IMPORT_NEEDED_EXIT_CODE = 2;
const CONFIG_PHP_UPDATE_NEEDED_EXIT_CODE = 1;
const DB_UPDATE_NEEDED_EXIT_CODE = 2;
const MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG = 'maintenance mode is active';
const ENV_CONFIG_FILE_PATH = 'app/etc/env.php';
const TMP_ENV_CONFIG_FILE_PATH = 'app/etc/env_tmp.php';

add('recipes', ['magento2']);

// Configuration

// By default setup:static-content:deploy uses `en_US`.
// To change that, simply put `set('static_content_locales', 'en_US de_DE');`
// in you deployer script.
set('static_content_locales', 'en_US');

// Configuration

// You can also set the themes to run against. By default it'll deploy
// all themes - `add('magento_themes', ['Magento/luma', 'Magento/backend']);`
// If the themes are set as a simple list of strings, then all languages defined in {{static_content_locales}} are
// compiled for the given themes.
// Alternatively The themes can be defined as an associative array, where the key represents the theme name and
// the key contains the languages for the compilation (for this specific theme)
// Example:
// set('magento_themes', ['Magento/luma']); - Will compile this theme with every language from {{static_content_locales}}
// set('magento_themes', [
//     'Magento/luma'   => null,                              - Will compile all languages from {{static_content_locales}} for Magento/luma
//     'Custom/theme'   => 'en_US fr_FR'                      - Will compile only en_US and fr_FR for Custom/theme
//     'Custom/another' => '{{static_content_locales}} it_IT' - Will compile all languages from {{static_content_locales}} + it_IT for Custom/another
// ]); - Will compile this theme with every language
set('magento_themes', [

]);

// Static content deployment options, e.g. '--no-parent'
set('static_deploy_options', '');

// Deploy frontend and adminhtml together as default
set('split_static_deployment', false);

// Use the default languages for the backend as default
set('static_content_locales_backend', '{{static_content_locales}}');

// backend themes to deploy. Only used if split_static_deployment=true
// This setting supports the same options/structure as {{magento_themes}}
set('magento_themes_backend', ['Magento/backend' => null]);

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

set('config_import_needed', function () {
    // detect if app:config:import is needed
    try {
        run('{{bin/php}} {{bin/magento}} app:config:status');
    } catch (RunException $e) {
        if ($e->getExitCode() == CONFIG_IMPORT_NEEDED_EXIT_CODE) {
            return true;
        }

        throw $e;
    }
    return false;
});

set('database_upgrade_needed', function () {
    // detect if setup:upgrade is needed
    try {
        run('{{bin/php}} {{bin/magento}} setup:db:status');
    } catch (RunException $e) {
        if ($e->getExitCode() == DB_UPDATE_NEEDED_EXIT_CODE) {
            return true;
        }

        throw $e;
    }
    try {
        run('{{bin/php}} {{bin/magento}} module:config:status');
    } catch (RunException $e) {
        if ($e->getExitCode() == CONFIG_PHP_UPDATE_NEEDED_EXIT_CODE) {
            return true;
        }

        throw $e;
    }

    return false;
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
    if (get('split_static_deployment')) {
        invoke('magento:deploy:assets:adminhtml');
        invoke('magento:deploy:assets:frontend');
    } else {
        if (count(get('magento_themes')) > 0 ) {
            foreach (get('magento_themes') as $theme) {
                $themesToCompile .= ' -t ' . $theme;
            }
        }
        run("{{bin/php}} {{release_or_current_path}}/bin/magento setup:static-content:deploy --content-version={{content_version}} {{static_deploy_options}} {{static_content_locales}} $themesToCompile -j {{static_content_jobs}}");
    }
});

desc('Deploys assets for backend only');
task('magento:deploy:assets:adminhtml', function () {
    magentoDeployAssetsSplit('backend');
});

desc('Deploys assets for frontend only');
task('magento:deploy:assets:frontend', function () {
    magentoDeployAssetsSplit('frontend');
});

/**
 * @phpstan-param 'frontend'|'backend' $area
 *
 * @throws ConfigurationException
 */
function magentoDeployAssetsSplit(string $area)
{
    if (!in_array($area, ['frontend', 'backend'], true)) {
        throw new ConfigurationException("\$area must be either 'frontend' or 'backend', '$area' given");
    }

    $isFrontend = $area === 'frontend';
    $suffix = $isFrontend
        ? ''
        : '_backend';

    $themesConfig = get("magento_themes$suffix");
    $defaultLanguages = get("static_content_locales$suffix");
    $useDefaultLanguages = array_is_list($themesConfig);

    /** @var list<string> $themes */
    $themes = $useDefaultLanguages
        ? array_values($themesConfig)
        : array_keys($themesConfig);

    $staticContentArea = $isFrontend
        ? 'frontend'
        : 'adminhtml';

    if ($useDefaultLanguages) {
        $themes = '-t '.implode(' -t ', $themes);

        run("{{bin/php}} {{bin/magento}} setup:static-content:deploy -f --area=$staticContentArea --content-version={{content_version}} {{static_deploy_options}} $defaultLanguages $themes -j {{static_content_jobs}}");
        return;
    }

    foreach ($themes as $theme) {
        $languages = parse($themesConfig[$theme] ?? $defaultLanguages);

        run("{{bin/php}} {{bin/magento}} setup:static-content:deploy -f --area=$staticContentArea --content-version={{content_version}} {{static_deploy_options}} $languages -t $theme -j {{static_content_jobs}}");
    }
}

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

desc('Set maintenance mode if needed');
task('magento:maintenance:enable-if-needed', function () {
    ! get('enable_zerodowntime') || get('database_upgrade_needed') || get('config_import_needed') ?
        invoke('magento:maintenance:enable') :
        writeln('Config and database up to date => no maintenance mode');
});

desc('Config Import');
task('magento:config:import', function () {
    if (get('config_import_needed')) {
        run('{{bin/php}} {{bin/magento}} app:config:import --no-interaction');
    } else {
        writeln('App config is up to date => import skipped');
    }
});

desc('Upgrades magento database');
task('magento:upgrade:db', function () {
    if (get('database_upgrade_needed')) {
        run("{{bin/php}} {{bin/magento}} setup:upgrade --keep-generated --no-interaction");
    } else {
        writeln('Database schema is up to date => upgrade skipped');
    }
});

desc('Flushes Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{bin/magento}} cache:flush");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:build',
    'magento:maintenance:enable-if-needed',
    'magento:config:import',
    'magento:upgrade:db',
    'magento:maintenance:disable',
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
    run('{{bin/tar}} --exclude-from={{artifact_excludes_file}} -czf {{artifact_path}} -C {{release_or_current_path}} .');
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

/**
 * Update cache id_prefix on deploy so that you are compiling against a fresh cache
 * Reference Issue: https://github.com/davidalger/capistrano-magento2/issues/151
 * To use this feature, add the following to your deployer scripts:
 * ```php
 * after('deploy:shared', 'magento:set_cache_prefix');
 * after('deploy:magento', 'magento:cleanup_cache_prefix');
 * ```
 **/
desc('Update cache id_prefix');
task('magento:set_cache_prefix', function () {
    //download current env config
    $tmpConfigFile = tempnam(sys_get_temp_dir(), 'deployer_config');
    download('{{deploy_path}}/shared/' . ENV_CONFIG_FILE_PATH, $tmpConfigFile);
    $envConfigArray = include($tmpConfigFile);
    //set prefix to `alias_releasename_`
    $prefixUpdate = get('alias') . '_' . get('release_name') . '_';

    //check for preload keys and update
    if (isset($envConfigArray['cache']['frontend']['default']['backend_options']['preload_keys'])) {
        $oldPrefix = $envConfigArray['cache']['frontend']['default']['id_prefix'];
        $preloadKeys = $envConfigArray['cache']['frontend']['default']['backend_options']['preload_keys'];
        $newPreloadKeys = [];
        foreach ($preloadKeys as $preloadKey) {
            $newPreloadKeys[] = preg_replace('/^' . $oldPrefix . '/', $prefixUpdate, $preloadKey);
        }
        $envConfigArray['cache']['frontend']['default']['backend_options']['preload_keys'] = $newPreloadKeys;
    }

    //update id_prefix to include release name
    $envConfigArray['cache']['frontend']['default']['id_prefix'] = $prefixUpdate;
    $envConfigArray['cache']['frontend']['page_cache']['id_prefix'] = $prefixUpdate;

    //Generate configuration array as string
    $envConfigStr = '<?php return ' . var_export($envConfigArray, true) . ';';
    file_put_contents($tmpConfigFile, $envConfigStr);
    //upload updated config to server
    upload($tmpConfigFile, '{{deploy_path}}/shared/' . TMP_ENV_CONFIG_FILE_PATH);
    //cleanup tmp file
    unlink($tmpConfigFile);
    //delete the symlink for env.php
    run('rm {{release_or_current_path}}/' . ENV_CONFIG_FILE_PATH);
    //link the env to the tmp version
    run('{{bin/symlink}} {{deploy_path}}/shared/' . TMP_ENV_CONFIG_FILE_PATH . ' {{release_path}}/' . ENV_CONFIG_FILE_PATH);
});

/**
 * After successful deployment, move the tmp_env.php file to env.php ready for next deployment
 */
desc('Cleanup cache id_prefix env files');
task('magento:cleanup_cache_prefix', function () {
    run('rm {{deploy_path}}/shared/' . ENV_CONFIG_FILE_PATH);
    run('rm {{release_or_current_path}}/' . ENV_CONFIG_FILE_PATH);
    run('mv {{deploy_path}}/shared/' . TMP_ENV_CONFIG_FILE_PATH . ' {{deploy_path}}/shared/' . ENV_CONFIG_FILE_PATH);
    // Symlink shared dir to release dir
    run('{{bin/symlink}} {{deploy_path}}/shared/' . ENV_CONFIG_FILE_PATH . ' {{release_path}}/' . ENV_CONFIG_FILE_PATH);
});

/**
 * Remove cron from crontab and kill running cron jobs
 * To use this feature, add the following to your deployer scripts:
 *  ```php
 *  after('magento:maintenance:enable-if-needed', 'magento:cron:stop');
 *  ```
 */
desc('Remove cron from crontab and kill running cron jobs');
task('magento:cron:stop', function () {
    if (has('previous_release')) {
        run('{{bin/php}} {{previous_release}}/{{magento_dir}}/bin/magento cron:remove');
    }

    run('pgrep -U "$(id -u)" -f "bin/magento +(cron:run|queue:consumers:start)" | xargs -r kill');
});

/**
 * Install cron in crontab
 * To use this feature, add the following to your deployer scripts:
 *   ```php
 *   after('magento:upgrade:db', 'magento:cron:install');
 *   ```
 */
desc('Install cron in crontab');
task('magento:cron:install', function () {
    run('cd {{release_or_current_path}}');
    run('{{bin/php}} {{bin/magento}} cron:install');
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
        'deploy:success'
]);

desc('Actually releases the artifact deployment');
task('artifact:deploy', [
        'artifact:prepare',
        'magento:maintenance:enable-if-needed',
        'magento:config:import',
        'magento:upgrade:db',
        'magento:maintenance:disable',
        'deploy:symlink',
        'artifact:finish',
]);

fail('artifact:deploy', 'deploy:failed');
