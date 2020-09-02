<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;

desc('Lock deploy');
task('deploy:lock', function () {
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");

    if ($locked) {
        throw new GracefulShutdownException(
            "Deploy locked.\n" .
            sprintf("Execute \"deploy:unlock\" task to unlock.")
        );
    } else {
        run("echo \"{{user}}\" > {{deploy_path}}/.dep/deploy.lock");
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});

desc('Check if deploy is unlocked');
task('deploy:is-unlocked', function () {
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");

    if ($locked) {
        writeln( 'Deploy is currently locked.');

        throw new GracefulShutdownException();
    }

    writeln( 'Deploy is currently unlocked.');
});
