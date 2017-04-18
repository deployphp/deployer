<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Cleaning up old releases');
task('cleanup', function () {
    $releases = get('releases_list');
    $keep = get('keep_releases');
    $sudo  = get('cleanup_use_sudo') ? 'sudo' : '';

    if ($keep === -1) {
        // Keep unlimited releases.
        return;
    }

    $keeps   = array_slice($releases, 0, $keep);
    $removes = array_diff($releases, $keeps);

    foreach ($removes as $release) {
        run("$sudo rm -rf {{deploy_path}}/releases/$release");
    }

    run("cd {{deploy_path}} && if [ -e release ]; then $sudo rm release; fi");
    run("cd {{deploy_path}} && if [ -h release ]; then $sudo rm release; fi");
});
