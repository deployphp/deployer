<?php

namespace Deployer;

require_once __DIR__ . '/symfony.php';

add('recipes', ['pimcore']);

add('shared_dirs', ['public/var', 'var/email', 'var/recyclebin', 'var/versions']);

add('writable_dirs', ['public/var']);

desc('Rebuild Pimcore Classes');
task('deploy:pimcore:rebuild-classes', function () {
    run('{{bin/php}} {{bin/console}} pimcore:deployment:classes-rebuild -c -d -n');
});

desc('Create Custom Layouts');
task('deploy:pimcore:custom-layouts-rebuild', function () {
    run('{{bin/php}} {{bin/console}} pimcore:deployment:custom-layouts-rebuild -c -d -n');
});
