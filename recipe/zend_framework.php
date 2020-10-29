<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['zend_framework']);

/**
 * Main task
 */
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:publish',
]);
