<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Creating symlink to release');
task('deploy:symlink', function () {
    if (run('if [[ "$(man mv)" =~ "--no-target-directory" ]]; then echo "true"; fi')->toBool()) {
        run("mv -T {{release_path}} {{current_path}}");
    } else {
        // Atomic symlink does not supported.
        // Will use simple≤ two steps switch.

        run("{{bin/symlink}} {{release_path}} {{current_path}}"); // Atomic override symlink.
        run("rm {{release_path}}"); // Remove release link.
    }
});
