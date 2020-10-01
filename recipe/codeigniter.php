<?php
namespace Deployer;

require_once __DIR__ . '/common.php';

// CodeIgniter shared dirs
set('shared_dirs', ['application/cache', 'application/logs']);

// CodeIgniter writable dirs
set('writable_dirs', ['application/cache', 'application/logs']);

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
