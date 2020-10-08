<?php
namespace Deployer;

desc('Creating symlink to release');
task('deploy:symlink', function () {
    if (get('use_atomic_symlink')) {
        run("mv -T {{deploy_path}}/release {{current_path}}");
    } else {
        // Atomic symlink does not supported.
        // Will use simple≤ two steps switch.

        run("cd {{deploy_path}} && {{bin/symlink}} {{release_path}} {{current_path}}"); // Atomic override symlink.
        run("cd {{deploy_path}} && rm release"); // Remove release link.
    }
});
