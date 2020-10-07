<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
