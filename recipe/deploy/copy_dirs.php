<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Copy directories');
task('deploy:copy_dirs', function () {
    $dirs = get('copy_dirs');
    $releases = get('releases_list');

    if (isset($releases[0])) {
        foreach ($dirs as $dir) {
            $path = "{{deploy_path}}/releases/{$releases[0]}/$dir";

            // Copy if dir exists.
            if (test("[ -d $path ]")) {
                run("cp -rpf $path {{release_path}}");
            }
        }
    }
});
