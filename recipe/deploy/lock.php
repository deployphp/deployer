<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

desc('Lock deploy');
task('deploy:lock', function () {
    $locked = run("if [ -f {{deploy_path}}/.dep/deploy.lock ]; then echo 'true'; fi")->toBool();

    if ($locked) {
        throw new GracefulShutdownException(
            "Deploy locked.\n" .
            "Run deploy:unlock command to unlock."
        );
    } else {
        run("touch {{deploy_path}}/.dep/deploy.lock");
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});
