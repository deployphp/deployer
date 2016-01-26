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

task('test:onlyForStage', function () {
    writeln('You should only see this for production');
})
    ->onlyForStage('production');

task('test', [
    'test:hello',
    'test:onlyForStage'
]);
