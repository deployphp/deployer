<?php
$loaded = false;

foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'composer install' . PHP_EOL
    );
}

require_once __DIR__ . '/recipe/AbstractTest.php';

define('DEPLOYER_BIN', __DIR__ . '/../bin/dep');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer');
