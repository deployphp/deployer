<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['codeigniter']);

// CodeIgniter shared dirs
set('shared_dirs', ['application/cache', 'application/logs']);

// CodeIgniter writable dirs
set('writable_dirs', ['application/cache', 'application/logs']);

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);
