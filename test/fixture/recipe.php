<?php

use Deployer\Functions;

Functions\localServer('localhost');
Functions\localServer('server1');
Functions\localServer('server2');

Functions\task('test', function () {
    Functions\writeln('Hello world!');
});
