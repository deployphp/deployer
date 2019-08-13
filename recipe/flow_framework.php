<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

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
task('deploy:run_migrations', function () {
    run('FLOW_CONTEXT={{flow_context}} {{bin/php}} {{release_path}}/{{flow_command}} doctrine:migrate');
})->desc('Apply database migrations');

/**
 * Publish resources
 */
task('deploy:publish_resources', function () {
    run('FLOW_CONTEXT={{flow_context}} {{bin/php}} {{release_path}}/{{flow_command}} resource:publish');
})->desc('Publish resources');

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:run_migrations',
    'deploy:publish_resources',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
