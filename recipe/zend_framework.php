<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
