<?php

namespace Deployer;

require 'recipe/common.php';

set('application', 'deployer');
set('repository', __REPOSITORY__);
set('shared_dirs', [
    'uploads',
    'storage/logs/',
    'storage/db',
]);
set('shared_files', [
    '.env',
    'config/test.yaml'
]);
set('keep_releases', 3);
set('http_user', false);

localhost('prod');

task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish'
]);

// Mock vendors installation to speed up tests.
task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        warning('To speed up composer installation setup "unzip" command with PHP zip extension.');
    }
    run('cd {{release_path}} && echo {{bin/composer}} {{composer_options}} 2>&1');
});

task('deploy:fail', [
    'deploy:prepare',
    'fail',
    'deploy:publish'
]);

task('fail', function () {
    run('false');
});

fail('deploy:fail', 'deploy:unlock');
