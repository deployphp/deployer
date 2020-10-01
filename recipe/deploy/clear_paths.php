<?php
namespace Deployer;

desc('Cleaning up files and/or directories');
task('deploy:clear_paths', function () {
    $paths = get('clear_paths');
    $sudo = get('clear_use_sudo') ? 'sudo' : '';
    $batch = 100;

    $commands = [];
    foreach ($paths as $path) {
        $commands[] = "$sudo rm -rf {{release_path}}/$path";
    }
    $chunks = array_chunk($commands, $batch);
    foreach ($chunks as $chunk) {
        run(implode('; ', $chunk));
    }
});
