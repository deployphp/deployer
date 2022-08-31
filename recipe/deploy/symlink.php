<?php
namespace Deployer;

// Use mv -T if available. Will check automatically.
set('use_atomic_symlink', function () {
    return commandSupportsOption('mv', '--no-target-directory');
});

desc('Creates symlink to release');
task('deploy:symlink', function () {
    if (get('use_atomic_symlink')) {
        run("mv -T {{deploy_path}}/release {{current_path}}");
    } else {
        // Atomic symlink does not supported.
        // Will use simple two steps switch.

        run("cd {{deploy_path}} && {{bin/symlink}} {{release_path}} {{current_path}}"); // Atomic override symlink.
        run("cd {{deploy_path}} && rm release"); // Remove release link.
    }
});
