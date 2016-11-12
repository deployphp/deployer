<?php
namespace Deployer;

require __DIR__ . '/../../recipe/common.php';

localServer('localhost');
localServer('server1');
localServer('server2');

task('test:hello', function () {
    writeln('Hello world!');
});

task('test', [
    'test:hello'
]);

task('test:hello', function () {
    runLocally('echo "hello"');
    writeln('Hello world!');
})->once();
