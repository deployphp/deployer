<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_files', ['wp-config.php']);
set('shared_dirs', ['wp-content/uploads']);
set('writable_dirs', ['wp-content/uploads']);

task('deploy', [
    'deploy:prepare',
    'deploy:publish',
])->desc('Deploy your project');

after('deploy', 'success');
