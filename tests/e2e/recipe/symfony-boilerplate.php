<?php declare(strict_types=1);
namespace Deployer;

require __DIR__ . '/hosts.php';
require_once __DIR__ . '/../../../recipe/symfony.php';

getHost('server.test')
    ->set('timeout', 600);

set('repository', 'https://github.com/deployphp/test-symfony.git');
