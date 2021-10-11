<?php
namespace Deployer;

use Deployer\Exception\GracefulShutdownException;

desc('Lock deploy');
task('deploy:lock', function () {
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");
    if ($locked) {
        throw new GracefulShutdownException(
            "Deploy locked.\n" .
            "Execute \"deploy:unlock\" task to unlock."
        );
    } else {
        run("echo \"{{user}}\" > {{deploy_path}}/.dep/deploy.lock");
    }
});

desc('Unlock deploy');
task('deploy:unlock', function () {
    run("rm -f {{deploy_path}}/.dep/deploy.lock");//always success
});

desc('Check if deploy is locked');
task('deploy:is_locked', function () {
    $locked = test("[ -f {{deploy_path}}/.dep/deploy.lock ]");
    if ($locked) {
        throw new GracefulShutdownException("Deploy is locked.");
    }
    info('Deploy is unlocked.');
});
