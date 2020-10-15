<?php declare(strict_types=1);
namespace Deployer;

// we need to user require instead of require_once, as the hosts HAVE to be loaded multiple times
require __DIR__ . '/deploy.php';

task('test:functions:run-with-placeholders', function (): void {
    $cmd = "echo 'placeholder %foo% %baz%'";
    $vars = [ 'foo' => '{{bar}}', 'baz' => 'xyz%' ];

    $output = run($cmd, [ 'vars' => $vars ]);
    output()->writeln($output); // we use this to skip \Deployer\parse() being called in normal \Deployer\writeln()
})->shallow();
