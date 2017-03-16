<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require 'recipe/common.php';


// Configuration

set('repository', __DIR__ . '/../fixture/repository');
set('shared_files', [
    'app/config/parameters.yml',
]);
set('shared_dirs', [
    'app/logs',
]);
set('writable_dirs', [
    'app/cache',
]);


// Servers

localhost()
    ->set('deploy_path', __DIR__ . '/.localhost');


// Tasks

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);


// Dummy

task('deploy:vendors', function () {
    run('echo {{env_vars}} {{bin/composer}} {{composer_options}}');
});
