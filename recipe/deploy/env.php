<?php

namespace Deployer;

set('dotenv_example', '.env.example');

desc('Configure .env file');
task('deploy:env', function () {
    cd('{{release_or_current_path}}');
    if (test('[ ! -e .env ] && [ -f {{dotenv_example}} ]')) {
        run('cp {{dotenv_example}} .env');
        set('new_deployment', true);
    }
});
