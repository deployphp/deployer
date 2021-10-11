<?php declare(strict_types=1);
namespace Deployer;

set('banner', <<<EOF
╭──────────────────────────────────────────────────────╮
│                                                      │
│   Update available! https://deployer.org/download/   │
│                                                      │
╰──────────────────────────────────────────────────────╯
EOF
);

set('old_version', function (): string {
    return explode(' ', run("bin/dep --version"))[1];
});

set('version', function (): string {
    cd('{{depl}}');
    writeln("Current version is {{old_version}}");
    return ask('Type new version (format is "1.1.1", without "v"; pre release "1.1.1-beta")', '--patch');
});
