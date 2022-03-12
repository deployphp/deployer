<?php
namespace Deployer;

// Use sudo in deploy:cleanup task for rm command.
set('cleanup_use_sudo', false);

desc('Cleanup old releases');
task('deploy:cleanup', function () {
    $releases = get('releases_list');
    $keep = get('keep_releases');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi");

    if ($keep > 0) {
        foreach (array_slice($releases, $keep) as $release) {
            run("$sudo rm -rf {{deploy_path}}/releases/$release");
        }
    }
});
