<?php
namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

desc('Locks deploy');
task('deploy:lock', function () {
    $user = escapeshellarg(get('user'));
    $locked = run("[ -f {{deploy_path}}/.dep/deploy.lock ] && echo +locked || echo $user > {{deploy_path}}/.dep/deploy.lock");
    if ($locked === '+locked') {
        $lockedUser = run("cat {{deploy_path}}/.dep/deploy.lock");
        throw new GracefulShutdownException(
            "Deploy locked by $lockedUser.\n" .
            "Execute \"deploy:unlock\" task to unlock."
        );
    }
});

desc('Unlocks deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});

desc('Checks if deploy is locked');
task('deploy:is_locked', function () {
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");
    if ($locked) {
        $lockedUser = run("cat {{deploy_path}}/.dep/deploy.lock");
        throw new GracefulShutdownException("Deploy is locked by $lockedUser.");
    }
    info('Deploy is unlocked.');
});
