<?php
namespace Deployer;

// Use sudo in deploy:cleanup task for rm command.
set('cleanup_use_sudo', false);

desc('Cleanups old releases');
task('deploy:cleanup', function () {
    $releases = get('releases_list');
    $keep = get('keep_releases');
    $sudo = get('cleanup_use_sudo') ? 'sudo' : '';
    $runOpts = [];

    if ($keep === -1) {
        // Keep unlimited releases.
        return;
    }

    while ($keep > 0) {
        array_shift($releases);
        --$keep;
    }

    foreach ($releases as $release) {
        run("$sudo rm -rf {{deploy_path}}/releases/$release", $runOpts);
    }

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi", $runOpts);
});
