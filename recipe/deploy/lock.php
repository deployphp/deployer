<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

desc('Lock deploy');
task('deploy:lock', function () {
    $locked = run("if [ -f {{deploy_path}}/.dep/deploy.lock ]; then echo 'true'; fi")->toBool();

    if ($locked) {
        throw new \RuntimeException(
            "Deploy locked.\n" .
            "Run deploy:unlock command to unlock."
        );
    } else {
        run("touch {{deploy_path}}/.dep/deploy.lock");
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm {{deploy_path}}/.dep/deploy.lock");
});
