<?php declare(strict_types=1);
namespace Deployer;

require_once __DIR__ . '/../../../recipe/provision.php';

host('provisioned.test')
    ->set('timeout', 300)
    ->setTag('e2e')
    ->setRemoteUser('root')
    ->setSshOptions(
        [
            'UserKnownHostsFile' => '/dev/null',
            'StrictHostKeyChecking' => 'no',
        ]
    );
