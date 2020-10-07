<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);
