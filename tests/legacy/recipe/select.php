<?php

namespace Deployer;

localhost('prod')->setLabels(['env' => 'prod']);
localhost('beta')->setLabels(['env' => 'dev']);
localhost('dev')->setLabels(['env' => 'dev']);

task('test', function () {
    on(select('env=dev'), function () {
        info('executing on {{alias}}');
    });
});
