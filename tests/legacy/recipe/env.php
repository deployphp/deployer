<?php

namespace Deployer;

localhost('prod');

set('env', [
    'VAR' => 'global',
]);

task('test', function () {
    info('global=' . run('echo $VAR'));
    info('local=' . run('echo $VAR', env: ['VAR' => 'local']));
    info('dotenv=' . run('echo $KEY'));
    info('dotenv=' . run('echo $KEY', env: ['KEY' => 'local']));
});

before('test', function () {
    run('mkdir -p {{deploy_path}}');
    run('echo KEY="\'Hello, world!\'" > {{deploy_path}}/.env');
    set('dotenv', '{{deploy_path}}/.env');
});
