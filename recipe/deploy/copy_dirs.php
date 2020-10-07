<?php
namespace Deployer;

desc('Copy directories');
task('deploy:copy_dirs', function () {
    if (has('previous_release')) {
        foreach (get('copy_dirs') as $dir) {
            if (test("[ -d {{previous_release}}/$dir ]")) {
                run("mkdir -p {{release_path}}/$dir");
                run("rsync -av {{previous_release}}/$dir/ {{release_path}}/$dir");
            }
        }
    }
});
