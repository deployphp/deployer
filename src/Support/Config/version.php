<?php declare(strict_types=1);
namespace Deployer;

set('version', function (): string {
    cd('{{depl}}');
    $version = explode(' ', run("bin/dep --version"))[1];
    writeln("Current version is $version");
    return ask('Type new version (1.2.3)', '--patch');
});
