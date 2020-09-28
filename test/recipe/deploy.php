<?php

namespace Deployer;

require 'recipe/common.php';

set('application', 'deployer');
set('repository', __DIR__ . '/repository');
set('shared_dirs', ['uploads']);
set('shared_files', ['.env']);
set('keep_releases', 3);

localhost('prod');
localhost('beta');

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

task('echo', function () {
    run('echo {{deploy_path}}');
});

task('once', function () {
    info('should be run only once');
})->once();

after('deploy', 'once');

task('ask', function () {
    $answer = ask('Question: What kind of bear is best?');
    writeln($answer);
});

