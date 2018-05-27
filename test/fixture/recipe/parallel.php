<?php
/* (c) Marc Legay <marc@ru3.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

require 'recipe/common.php';

// Hosts

localhost('host[1:2]')
    ->set('deploy_path', __DIR__ . '/tmp/localhost');


// Tasks

desc('Deploy your project');
task('deploy', function () {
    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');
    cd('{{deploy_path}}');
    run('touch deployed-{{hostname}}');
})->once();
