<?php

require __DIR__ . '/../../recipe/common.php';

localServer('localhost');
localServer('server1');
localServer('server2');
localServer('server3')
    ->stage('production');
localServer('server4')
    ->stage('production');

task('test:hello', function () {
    writeln('Hello world!');
});

task('test:onlyFor', function () {
    writeln('You should only see this for production');
})
    ->onlyFor('production');

task('test', [
    'test:hello',
    'test:onlyFor'
]);
