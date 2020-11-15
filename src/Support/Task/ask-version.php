<?php declare(strict_types=1);
namespace Deployer;

task('ask:version', function (): void {
    cd('{{depl}}');
    $version = explode(' ', run("bin/dep --version"))[1];
    writeln("Current version is $version");
    $newVersion = ask('Type new version (1.2.3)', '--patch');
    set('version', $newVersion);
});
