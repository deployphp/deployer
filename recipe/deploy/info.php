<?php
namespace Deployer;

desc('Displays info about deployment');
task('deploy:info', function () {
    $target = get('target');
    try {
        $target = runLocally("git rev-parse --abbrev-ref $target");
    } catch (\Throwable $exception) {
        // noop
    }
    info("deploying <fg=magenta;options=bold>$target</>");
});
