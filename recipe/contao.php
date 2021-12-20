<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['contao']);

add('shared_files', ['config/parameters.yml']);

add('shared_dirs', [
    'assets/images',
    'contao-manager',
    'files',
    'public/share',
    'system/config',
    'var/backups',
    'var/logs',
]);

set('bin/console', function () {
    return '{{release_or_current_path}}/vendor/bin/contao-console';
});

set('contao_version', function () {
    return run('{{bin/console}} contao:version');
});

// The public path is the path to be set as DocumentRoot and is defined in the `composer.json` of the project
// but defaults to `public` from Contao 5.0 on.
// This path is relative from the {{current_path}}, see [`recipe/provision/website.php`](/docs/recipe/provision/website.php#public_path).
set('public_path', function () {
    $composerConfig = json_decode(file_get_contents('./composer.json'), true, 512, JSON_THROW_ON_ERROR);

    return $composerConfig['extra']['public-dir'] ?? 'public';
});

// This task updates the database. A database backup is saved automatically as a default.
desc('Run Contao migrations');
task('contao:migrate', function () {
    run('{{bin/php}} {{bin/console}} contao:migrate {{console_options}}');
});

// Optional task to download the `contao-manager.phar.php` in the public path. Make sure to
// set a password on first access!
desc('Download the Contao Manager');
task('contao:manager:download', function () {
    run('curl -LsO https://download.contao.org/contao-manager/stable/contao-manager.phar && mv contao-manager.phar {{release_or_current_path}}/{{public_path}}/contao-manager.phar.php');
});

desc('Lock the Contao Install Tool');
task('contao:install:lock', function () {
    run('{{bin/php}} {{bin/console}} contao:install:lock {{console_options}}');
});

desc('Enable maintenance mode');
task('contao:maintenance:enable', function () {
    run('{{bin/php}} {{bin/console}} contao:maintenance-mode --enable {{console_options}}');
});

desc('Disable maintenance mode');
task('contao:maintenance:disable', function () {
    run('{{bin/php}} {{bin/console}} contao:maintenance-mode --disable {{console_options}}');
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
