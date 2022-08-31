<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['composer']);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);
