<?php declare(strict_types=1);
namespace Deployer;

host('server.test')
    ->setDeployPath('/var/www/html')
    ->set('bin/php', '/usr/local/bin/php')
    ->setTag('e2e')
    ->setRemoteUser('deployer')
    ->set('timeout', 600)
    ->setSshArguments([
        '-o UserKnownHostsFile=/dev/null',
        '-o StrictHostKeyChecking=no',
    ]);
