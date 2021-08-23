<?php

namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['pimcore']);

add('shared_dirs', ['public/var', 'var/email', 'var/recyclebin', 'var/versions']);

add('writable_dirs', ['public/var']);

desc('Rebuild Pimcore Classes');
task('pimcore:rebuild-classes', function () {
    run('{{bin/php}} {{bin/console}} pimcore:deployment:classes-rebuild --create --delete --no-interaction');
});

desc('Create Custom Layouts');
task('pimcore:custom-layouts-rebuild', function () {
    run('{{bin/php}} {{bin/console}} pimcore:deployment:custom-layouts-rebuild --create --delete --no-interaction');
});

task('pimcore:deploy', [
    'pimcore:rebuild-classes',
    'pimcore:custom-layouts-rebuild'
]);

after('deploy:vendors', 'pimcore:deploy');
