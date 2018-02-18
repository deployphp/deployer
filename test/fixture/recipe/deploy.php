<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require 'recipe/common.php';


// Configuration

set('repository', __DIR__ . '/../repository');
set('http_user', getenv('USER'));

set('media_dir', 'public/media');
set('parameters.yml', 'app/config/parameters.yml');

set('shared_files', [
    '{{parameters.yml}}',
]);

set('shared_dirs', [
    'app/logs',
    '{{media_dir}}',
]);

set('writable_dirs', [
    'app/cache',
]);


// Hosts

localhost()
    ->set('deploy_path', __DIR__ . '/tmp/localhost');


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

desc('Test deploy fail');
task('deploy_fail', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'fail',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

task('fail', 'unknown_command');

// If deploy fails automatically unlock

fail('deploy_fail', 'deploy:unlock');

// Dummy

task('deploy:vendors', function () {
    run('echo {{bin/composer}} {{composer_options}}');
});
