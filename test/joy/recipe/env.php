<?php

namespace Deployer;

localhost('prod');

set('env', [
    'VAR' => 'global',
]);

task('test', function () {
    info('global=' . run('echo $VAR'));
    info('local=' . run('echo $VAR', ['env' => ['VAR' => 'local']]));
});
