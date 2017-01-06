<?php
namespace Deployer;

require __DIR__ . '/../../recipe/common.php';

local\server('localhost');
local\server('server1');
local\server('server2');

task('test:hello', function () {
    writeln('Hello world!');
});

task('test', [
    'test:hello'
]);

task('test:hello', function () {
    local\run('echo "hello"');
    writeln('Hello world!');
})->once();
