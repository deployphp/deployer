<?php
/* (c) Joren Van Hee
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require_once __DIR__ . '/common.php';

set('shared_dirs', [
    'storage',
]);

set('shared_files', [
    '.env',
]);

// https://docs.craftcms.com/v3/installation.html#step-2-set-the-file-permissions
set('writable_dirs', [
    'storage',
    'vendor',
    'web/cpresources',
]);

desc('Execute craft migrate/all');
task('craft:migrate:all', function () {
    run('{{bin/php}} {{release_path}}/craft migrate/all');
});

desc('Execute craft migrate/up');
task('craft:migrate:up', function () {
    run('{{bin/php}} {{release_path}}/craft migrate/up');
});

desc('Execute craft project-config/sync');
task('craft:project_config:sync', function () {
    run('{{bin/php}} {{release_path}}/craft project-config/sync');
});

desc('Execute craft cache/flush-all');
task('craft:cache:flush_all', function () {
    run('{{bin/php}} {{release_path}}/craft cache/flush-all');
});

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');
