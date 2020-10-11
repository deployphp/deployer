<?php declare(strict_types=1);
namespace Deployer;

require_once __DIR__ . '/deploy.php';

task('test:functions:run-with-placeholders', function (): void {
    $cmd = "echo 'placeholder %foo% %baz%'";
    $vars = [ 'foo' => '{{bar}}', 'baz' => 'xyz%' ];

    $output = run($cmd, [ 'vars' => $vars ]);
    output()->writeln($output); // we use this to skip \Deployer\parse() being called in normal \Deployer\writeln()
})->shallow();
