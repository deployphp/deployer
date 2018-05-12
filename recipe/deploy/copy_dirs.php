<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Copy directories');
task('deploy:copy_dirs', function () {
    if (has('previous_release')) {
        foreach (get('copy_dirs') as $dir) {
            if (test("[ -d {{previous_release}}/$dir ]")) {
                run("mkdir -p {{release_path}}/$dir");
                run("rsync -av {{previous_release}}/$dir/ {{release_path}}/$dir");
            }
        }
    }
});
