<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['pimcore']);

add('shared_dirs', ['public/var', 'var/email', 'var/recyclebin', 'var/versions']);

add('shared_files', ['config/local/database.yaml']);

add('writable_dirs', ['public/var', 'var/cache/dev']);

desc('Rebuilds Pimcore Classes');
task('pimcore:rebuild-classes', function () {
    run('{{bin/console}} pimcore:deployment:classes-rebuild --create-classes --delete-classes --no-interaction');
});

desc('Creates Custom Layouts');
task('pimcore:custom-layouts-rebuild', function () {
    run('{{bin/console}} pimcore:deployment:custom-layouts-rebuild --create-custom-layouts --delete-custom-layouts --no-interaction');
});

desc('Removes cache');
task('pimcore:cache_clear', function () {
    run('rm -rf {{release_or_current_path}}/var/cache/dev/*');
});

task('pimcore:deploy', [
    'pimcore:rebuild-classes',
    'pimcore:custom-layouts-rebuild',
    'pimcore:cache_clear'
]);

after('deploy:vendors', 'pimcore:deploy');
