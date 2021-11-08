<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['fuelphp']);

// FuelPHP 1.x shared dirs
set('shared_dirs', [
    'fuel/app/cache', 'fuel/app/logs',
]);

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);
