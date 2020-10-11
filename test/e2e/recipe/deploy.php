<?php declare(strict_types=1);
namespace Deployer;

set('application', 'deployer-e2e');
host('server.test')
    ->setTag('e2e')
    ->setRemoteUser('deployer')
    ->setSshOptions(
        [
            'UserKnownHostsFile' => '/dev/null',
            'StrictHostKeyChecking' => 'no',
        ]
    );
