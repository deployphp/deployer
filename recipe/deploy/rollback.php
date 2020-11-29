<?php

namespace Deployer;

use Deployer\Exception\Exception;

desc('Rollback to previous release');
task('rollback', function () {
    $currentRelease = basename(run('readlink {{current_path}}'));
    $releases = get('releases_list');

    $releasesBeforeCurrent = [];
    $foundCurrent = false;
    foreach ($releases as $r) {
        if ($r === $currentRelease) {
            $foundCurrent = true;
            continue;
        }
        if ($foundCurrent) {
            $releasesBeforeCurrent[] = $r;
        }
    }

    while (isset($releasesBeforeCurrent[0])) {
        $releaseDir = "{{deploy_path}}/releases/{$releasesBeforeCurrent[0]}";

        // Skip all bad releases.
        if (test("[ -f $releaseDir/BAD_RELEASE ]")) {
            array_shift($releasesBeforeCurrent);
            continue;
        }

        // Symlink to old release.
        run("cd {{deploy_path}} && {{bin/symlink}} $releaseDir {{current_path}}");

        // Mark release as bad.
        $date = run('date +"%Y%m%d%H%M%S"');
        run("echo '$date,{{user}}' > {{deploy_path}}/releases/$currentRelease/BAD_RELEASE");

        writeln("<info>rollback</info> to release {$releasesBeforeCurrent[0]} was <success>successful</success>");
        return;
    }

    throw new Exception("No more releases you can revert to.");
});
