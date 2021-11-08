<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['joomla']);

set('shared_files', ['configuration.php']);
set('shared_dirs', ['images']);
set('writable_dirs', ['images']);

desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:publish',
]);
