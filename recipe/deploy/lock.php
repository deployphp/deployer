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

    if (get('holds_deploy_lock')) {
        // we already hold the lock
        return;
    }

    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");

    if ($locked) {
        $stage = input()->hasArgument('stage') ? ' ' . input()->getArgument('stage') : '';

        throw new GracefulShutdownException(
            "Deploy locked.\n" .
            sprintf('Execute "dep deploy:unlock%s" to unlock.', $stage)
        );
    } else {
        run("touch {{deploy_path}}/.dep/deploy.lock");
        set('holds_deploy_lock', true);
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
    set('holds_deploy_lock', false);
});
