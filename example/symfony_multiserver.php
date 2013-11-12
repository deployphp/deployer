<?php
require __DIR__ . '/../vendor/autoload.php';
deployer();

define('local', __DIR__);
define('remote', '/home/www.domain.com');
define('user',   'webmaster');

ignore([
    'Tests/*',
    'phpunit/*',
    'app/cache/*',
    'app/logs/*',
    'web/bundles/*',
]);

task('connect', 'Connect to servers.', function () {
    connect('job.domain.com', user, rsa('~/.ssh/id_rsa'), 'job');
    connect('node1.domain.com', user, rsa('~/.ssh/id_rsa'), 'node');
    connect('node2.domain.com', user, rsa('~/.ssh/id_rsa'), 'node');
    connect('node3.domain.com', user, rsa('~/.ssh/id_rsa'), 'node');
    cd(remote);
});

task('upload', 'Upload files to remote servers.', function () {
    upload(local, remote);
});

task('clone', 'Clone repository on remote servers.', function () {
    run('git clone git@github.com:user/repository.git .');
});

task('pull', 'Update repository via git pull.', function () {
    run('git reset --hard');
    run('git pull');
});

task('upload_parameters', 'Upload server parameters.yml files.', function () {
    upload(local . '/share/parameters.yml', remote . '/app/config/parameters.yml');
});

task('vendors', 'Update vendors on remote servers.', function () {
    run('composer update --no-dev');
});

task('cache', 'Clear and warm up application cache.', function () {
    run('php app/console cache:clear --env=prod --no-debug');
});

task('assetic', 'Dump assetic assets.', function () {
    run('php app/console assetic:dump --env=prod --no-debug');
});

task('symlink_www', 'Symlink www folder to web folder.', function () {
    run('rm www && ln -s web www');
});

task('migrate', 'Run migrations on master server.', function () {
    run('php app/console doctrine:migrations:migrate --no-interaction', 'job');
});

task('install', 'Install application on servers.', ['connect', 'clone']);
task('update', 'Update servers application.', ['connect', 'pull', 'upload_parameters', 'vendors', 'cache', 'migrate', 'assetic', 'symlink_www']);

start();