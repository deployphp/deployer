<?php

namespace Deployer;

desc('Configure .env file');
task('deploy:env', function () {
    cd('{{release_path}}');
    if (test('[ -f .env.example ]')) {
        run('cp .env.example .env');
        set('new_deployment', true);
    }
});
