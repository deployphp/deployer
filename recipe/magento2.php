<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

use Deployer\Exception\RunException;
use Symfony\Component\Process\Exception\ProcessFailedException;

const DB_UPDATE_NEEDED_EXIT_CODE = 2;
const MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG = 'maintenance mode is active';

add('recipes', ['magento2']);

// Configuration

// By default setup:static-content:deploy uses `en_US`.
// To change that, simply put set('static_content_locales', 'en_US de_DE');`
// in you deployer script.
set('static_content_locales', 'en_US');

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
    run("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy {{static_content_locales}}");
});

desc('Enable maintenance mode');
task('magento:maintenance:enable', function () {
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/bin/magento maintenance:enable; fi");
});

desc('Disable maintenance mode');
task('magento:maintenance:disable', function () {
    run("if [ -d $(echo {{current_path}}) ]; then {{bin/php}} {{current_path}}/bin/magento maintenance:disable; fi");
});

desc('Upgrade magento database');
task('magento:upgrade:db', function () {
    $databaseUpgradeNeeded = false;

    try {
        run('{{bin/php}} {{release_path}}/bin/magento setup:db:status');
    } catch (ProcessFailedException $e) {
        if ($e->getProcess()->getExitCode() == DB_UPDATE_NEEDED_EXIT_CODE) {
            $databaseUpgradeNeeded = true;
        } else {
            throw $e;
        }
    } catch (RunException $e) {
        if ($e->getExitCode() == DB_UPDATE_NEEDED_EXIT_CODE) {
            $databaseUpgradeNeeded = true;
        } else {
            throw $e;
        }
    }

    if ($databaseUpgradeNeeded) {
        // check whether maintenance mode is enabled or not in order to keep the status afterwards
        $maintenanceModeStatusOutput = run("{{bin/php}} {{release_path}}/bin/magento maintenance:status");
        $maintenanceModeActive = strpos($maintenanceModeStatusOutput, MAINTENANCE_MODE_ACTIVE_OUTPUT_MSG) !== false;

        if (!$maintenanceModeActive) {
            invoke('magento:maintenance:enable');
        }

        run("{{bin/php}} {{release_path}}/bin/magento setup:upgrade --keep-generated");

        if (!$maintenanceModeActive) {
            invoke('magento:maintenance:disable');
        }
    }
});

desc('Flush Magento Cache');
task('magento:cache:flush', function () {
    run("{{bin/php}} {{release_path}}/bin/magento cache:flush");
});

desc('Magento2 deployment operations');
task('deploy:magento', [
    'magento:compile',
    'magento:deploy:assets',
    'magento:upgrade:db',
    'magento:cache:flush'
]);

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:magento',
    'deploy:publish',
]);

after('deploy:failed', 'magento:maintenance:disable');
