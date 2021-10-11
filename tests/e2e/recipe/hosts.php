<?php declare(strict_types=1);

namespace Deployer;

set('application', 'deployer-e2e');
host('server.test')
    ->setDeployPath('/var/www/html')
    ->set('bin/php', '/usr/local/bin/php')
    ->setTag('e2e')
    ->setRemoteUser('deployer')
    ->setSshArguments([
        '-o UserKnownHostsFile=/dev/null',
        '-o StrictHostKeyChecking=no',
    ]);
