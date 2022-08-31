<?php declare(strict_types=1);
namespace Deployer;

// we need to user require instead of require_once, as the hosts HAVE to be loaded multiple times
require_once __DIR__ . '/hosts.php';

task('test:misc:sudo-write-user', function (): void {
    $cmd = 'sudo bash -c \'echo Current user is: $USER\'';
    $output = run($cmd);
    writeln($output);
});

