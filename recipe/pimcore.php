<?php
namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['pimcore']);

add('shared_dirs', ['public/var', 'var/email', 'var/recyclebin', 'var/versions']);

add('writable_dirs', ['public/var']);

desc('Rebuilds Pimcore Classes');
task('pimcore:rebuild-classes', function () {
    run('{{bin/console}} pimcore:deployment:classes-rebuild --create-classes --delete-classes --no-interaction');
});

desc('Creates Custom Layouts');
task('pimcore:custom-layouts-rebuild', function () {
    run('{{bin/console}} pimcore:deployment:custom-layouts-rebuild --create-custom-layouts --delete-custom-layouts --no-interaction');
});

task('pimcore:deploy', [
    'pimcore:rebuild-classes',
    'pimcore:custom-layouts-rebuild'
]);

after('deploy:vendors', 'pimcore:deploy');
