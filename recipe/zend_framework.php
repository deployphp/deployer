<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['zend_framework']);

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
