<?php
namespace Deployer;

// Creates patch of local changes and pushes them on host.
// And applies to current_path. Push can be done many times.
// The task purpose to be used only for development.
desc('Pushes local changes to remote host');
task('push', function () {
    $files = explode("\n", runLocally("git diff --name-only HEAD"));

    info('uploading:');
    foreach ($files as $file) {
        writeln(" - $file");
    }

    upload(
        $files,
        '{{current_path}}',
        ['progress_bar' => false, 'options' => ['--relative']]
    );

    // Mark this release as dirty.
    run("echo '{{user}}' > {{current_path}}/DIRTY_RELEASE");
});
