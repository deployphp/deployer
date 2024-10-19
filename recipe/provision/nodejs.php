<?php

namespace Deployer;

use function Deployer\Support\starts_with;

set('node_version', '23.x');

desc('Installs npm packages');
task('provision:node', function () {
    if (has('nodejs_version')) {
        throw new \RuntimeException('nodejs_version is deprecated, use node_version_version instead.');
    }
    $arch = run('uname -m');

    if ($arch === 'arm' || starts_with($arch, 'armv7')) {
        $filename = 'fnm-arm32';
    } elseif (starts_with($arch, 'aarch') || starts_with($arch, 'armv8')) {
        $filename = 'fnm-arm64';
    } else {
        $filename = 'fnm-linux';
    }

    $url = "https://github.com/Schniz/fnm/releases/latest/download/$filename.zip";
    run("curl -sSL $url --output /tmp/$filename.zip");

    run("unzip /tmp/$filename.zip -d /tmp");

    run("mv /tmp/fnm /usr/local/bin/fnm");
    run('chmod +x /usr/local/bin/fnm');

    run('fnm install {{node_version}}');
    appendToFile('~/.bashrc', 'eval "`fnm env`"');
})
    ->oncePerNode();
