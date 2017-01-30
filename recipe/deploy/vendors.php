<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Installing vendors');
task('deploy:vendors', function () {
    $releases = get('releases_list');
    if (isset($releases[1])) {
        if (run("if [ -d {{deploy_path}}/releases/{$releases[1]}/vendor ]; then echo 'true'; fi")->toBool()) {
            run("cp --recursive {{deploy_path}}/releases/{$releases[1]}/vendor {{release_path}}");
        }
    }
    run('cd {{release_path}} && {{env_vars}} {{bin/composer}} {{composer_options}}');
});
