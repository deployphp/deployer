<?php
require_once __DIR__ . '/vendor/autoload.php';
new Deployer\Tool();

define('local', __DIR__);
define('remote', '/home/u77602/test.xu.su');

ignore([
    'Tests/*',
    'phpunit/*'
]);

task('connect', 'Connect to production server.', function () {
    connect('u77602.ssh.masterhost.ru', 'u77602', rsa('~/.ssh/id_rsa'));
    cd(remote . '/www');
});

task('upload', function () {
    upload(local . '/', remote . '/www');
});

task('pull', function () {
    run('git pull');
});

task('dev', ['connect', 'pull']);

task('prod', 'Deploy on production server', ['connect', 'upload']);

start();

