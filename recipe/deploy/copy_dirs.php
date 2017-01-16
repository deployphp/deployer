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

    foreach ($dirs as $dir) {
        // Delete directory if exists.
        run("if [ -d $(echo {{release_path}}/$dir) ]; then rm -rf {{release_path}}/$dir; fi");

        // Copy directory.
        run("if [ -d $(echo {{current_path}}/$dir) ]; then cp -rpf {{current_path}}/$dir {{release_path}}/$dir; fi");
    }
});
