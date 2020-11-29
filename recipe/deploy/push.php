<?php

namespace Deployer;

// Creates patch of local changes and pushes them on host.
// And applies to current_path. Push can be done many times.
// The task purpose to be used only for development.
desc('Push local changes to remote host');
task('push', function () {
    $patchFile = currentHost()->getAlias() . ".patch";

    runLocally("git diff HEAD > $patchFile");
    upload($patchFile, "{{current_path}}/$patchFile", ['progress_bar' => false]);
    runLocally("rm $patchFile");

    cd('{{current_path}}');
    run("git stash");
    run("git apply $patchFile");
    run("rm $patchFile");
    run("git add .");

    $status = run("git -c color.diff=always diff --stat HEAD");
    foreach (explode("\n", $status) as $line) {
        writeln($line);
    }
});
