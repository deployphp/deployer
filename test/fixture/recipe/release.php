<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require 'recipe/common.php';

localhost()
    ->set('deploy_path', __DIR__ . '/tmp/localhost');

desc('Deploy ');
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:symlink',
    'result',
]);

task('result', function () {
    writeln('release_path {{release_path}}');
    if (has('previous_release')) {
        writeln('previous_release {{previous_release}}');
    }
});
