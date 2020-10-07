<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

// CodeIgniter shared dirs
set('shared_dirs', ['application/cache', 'application/logs']);

// CodeIgniter writable dirs
set('writable_dirs', ['application/cache', 'application/logs']);

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
