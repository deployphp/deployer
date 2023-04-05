<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['yii']);

// Yii shared dirs
set('shared_dirs', ['runtime']);

// Yii writable dirs
set('writable_dirs', ['runtime']);

task('deploy:migrate', function () {
    run('cd {{release_path}} && php yii migrate');
});

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:migrate',
    'deploy:publish',
]);
