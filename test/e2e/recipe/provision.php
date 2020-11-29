<?php declare(strict_types=1);
namespace Deployer;

require_once __DIR__ . '/../../../recipe/provision.php';

host('provisioned.test')
    ->set('timeout', 300)
    ->setTag('e2e')
    ->setRemoteUser('root')
    ->setSshArguments([
        '-o UserKnownHostsFile=/dev/null',
        '-o StrictHostKeyChecking=no',
    ]);

task('version:get', [
    'version:get:nginx',
    'version:get:php',
    'version:get:php-fpm',
]);

task('version:get:nginx', function () {
    $versionCmdOutput = run('nginx -v 2>&1'); // nginx prints version info to stderr, so we redirect it to stdout
    output()->writeln($versionCmdOutput);
});

task('version:get:php', function () {
    $versionCmdOutput = run('php -v');
    output()->writeln($versionCmdOutput);
});

task('version:get:php-fpm', function () {
    $versionCmdOutput = run('php-fpm{{php_version}} -v');
    output()->writeln($versionCmdOutput);
});

after('provision', 'version:get');
