<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['contao']);

// The public path is the path to be set as DocumentRoot and is defined in the `composer.json` of the project
// but defaults to `public` from Contao 5.0 on.
// This path is relative from the {{current_path}}, see [`recipe/provision/website.php`](/docs/recipe/provision/website.php#public_path).
set('public_path', function () {
    $composerConfig = json_decode(file_get_contents('./composer.json'), true, 512, JSON_THROW_ON_ERROR);

    return $composerConfig['extra']['public-dir'] ?? 'public';
});

add('shared_files', ['config/parameters.yml']);

add('shared_dirs', [
    'assets/images',
    'contao-manager',
    'files',
    '{{public_path}}/share',
    'system/config',
    'var/backups',
    'var/logs',
]);

set('bin/console', function () {
    return '{{bin/php}} {{release_or_current_path}}/vendor/bin/contao-console';
});

set('contao_version', function () {
    return run('{{bin/console}} contao:version');
});

// This task updates the database. A database backup is saved automatically as a default.
//
// To automatically drop the obsolete database structures, you can override the task as follows:
//
// ```php
// task('contao:migrate', function () {
//     run('{{bin/php}} {{bin/console}} contao:migrate --with-deletes {{console_options}}');
// });
// ```
desc('Run Contao migrations');
task('contao:migrate', function () {
    run('{{bin/console}} contao:migrate {{console_options}}');
});

// Downloads the `contao-manager.phar.php` into the public path.
desc('Download the Contao Manager');
task('contao:manager:download', function () {
    run('curl -LsO https://download.contao.org/contao-manager/stable/contao-manager.phar && mv contao-manager.phar {{release_or_current_path}}/{{public_path}}/contao-manager.phar.php');
});

// Locks the Contao install tool which is useful if you don't use it.
desc('Lock the Contao Install Tool');
task('contao:install:lock', function () {
    run('{{bin/console}} contao:install:lock {{console_options}}');
});

// Locks the Contao Manager which is useful if you only need the API of the Manager rather than the UI.
desc('Lock the Contao Manager');
task('contao:manager:lock', function () {
    cd('{{release_or_current_path}}');
    run('echo "99" > contao-manager/login.lock');
});

desc('Enable maintenance mode');
task('contao:maintenance:enable', function () {
    // Enable maintenance mode in both the current and release build, so that the maintenance mode will be enabled
    // for the current installation before the symlink changes and the new installation after the symlink changed.
    foreach (array_unique([parse('{{current_path}}'), parse('{{release_or_current_path}}')]) as $path) {
        // The current path might not be present during first deploy.
        if (!test("[ -d $path ]")) {
            continue;
        }

        cd($path);
        run('{{bin/console}} contao:maintenance-mode enable {{console_options}}');
    }
});

desc('Disable maintenance mode');
task('contao:maintenance:disable', function () {
    foreach (array_unique([parse('{{current_path}}'), parse('{{release_or_current_path}}')]) as $path) {
        if (!test("[ -d $path ]")) {
            continue;
        }

        cd($path);
        run('{{bin/console}} contao:maintenance-mode disable {{console_options}}');
    }
});

desc('Deploy the project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'contao:maintenance:enable',
    'contao:migrate',
    'contao:maintenance:disable',
    'deploy:publish',
]);
