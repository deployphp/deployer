<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['wordpress']);

set('shared_files', ['wp-config.php']);
set('shared_dirs', ['wp-content/uploads']);
set('writable_dirs', ['wp-content/uploads']);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:publish',
]);
