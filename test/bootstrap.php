<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/src/Helper/DeployerHelper.php';

$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'includePaths' => [__DIR__ . '/../src'],
    'cacheDir' => sys_get_temp_dir(),
]);
