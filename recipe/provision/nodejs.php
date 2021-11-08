<?php
namespace Deployer;

// Node.js version from https://github.com/nodesource/distributions.
set('nodejs_version', 'node_16.x');

desc('Installs npm packages');
task('provision:npm', function () {
    run('npm install -g fx zx pm2');
    run('pm2 startup');
})
    ->oncePerNode();
