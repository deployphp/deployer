<?php

declare(strict_types=1);

namespace Deployer;

// we need to user require instead of require_once, as the hosts HAVE to be loaded multiple times
require_once __DIR__ . '/hosts.php';

task('test:functions:run-with-placeholders', function (): void {
    $cmd = 'echo "placeholder $foo $baz"';
    $env = ['foo' => '{{bar}}', 'baz' => 'xyz%'];

    $output = run($cmd, ['env' => $env]);
    output()->writeln($output); // we use this to skip \Deployer\parse() being called in normal \Deployer\writeln()
});
