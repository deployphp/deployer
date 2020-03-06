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
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");

    if ($locked) {
        $stage = input()->hasArgument('stage') ? ' ' . input()->getArgument('stage') : '';

        throw new GracefulShutdownException(
            "Deploy locked.\n" .
            sprintf('Execute "'.  Deployer::getCalledScript() .' deploy:unlock%s" to unlock.', $stage)
        );
    } else {
        run("touch {{deploy_path}}/.dep/deploy.lock");
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});
