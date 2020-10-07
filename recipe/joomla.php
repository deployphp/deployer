<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_files', ['configuration.php']);
set('shared_dirs', ['images']);
set('writable_dirs', ['images']);

task('deploy', [
    'deploy:prepare',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
