<?php

namespace Deployer;

localhost('prod');
localhost('beta');

task('test_once', function () {
    writeln('SHOULD BE ONCE');
})->once();
