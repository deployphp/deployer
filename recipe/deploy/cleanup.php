<?php
namespace Deployer;

desc('Cleaning up old releases');
task('deploy:cleanup', function () {
    $releases = get('releases_list');
    $keep = get('keep_releases');
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
        run("ionice -c 3 nice -19 find {{deploy_path}}/releases/$release/ -delete", $runOpts);
        run("rm -rf {{deploy_path}}/releases/$release", $runOpts);
    }

    run("cd {{deploy_path}} && if [ -e release ]; then rm release; fi", $runOpts);
});
