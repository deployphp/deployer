<?php

localServer('localhost');
localServer('server1');
localServer('server2');

task('test', function () {
    writeln('Hello world!');
});
