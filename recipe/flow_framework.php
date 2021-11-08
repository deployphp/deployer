<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['flow_framework']);

// Flow-Framework application-context
set('flow_context', 'Production');

// Flow-Framework cli-command
set('flow_command', 'flow');

// Flow-Framework shared directories
set('shared_dirs', [
    'Data/Persistent',
    'Data/Logs',
    'Configuration/{{flow_context}}'
]);

/**
 * Apply database migrations
 */
desc('Applies database migrations');
task('deploy:run_migrations', function () {
    run('FLOW_CONTEXT={{flow_context}} {{bin/php}} {{release_or_current_path}}/{{flow_command}} doctrine:migrate');
});

/**
 * Publish resources
 */
desc('Publishes resources');
task('deploy:publish_resources', function () {
    run('FLOW_CONTEXT={{flow_context}} {{bin/php}} {{release_or_current_path}}/{{flow_command}} resource:publish');
});

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'deploy:run_migrations',
    'deploy:publish_resources',
    'deploy:publish',
]);
