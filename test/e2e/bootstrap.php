<?php
$loaded = false;
$file = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($file)) {
    require $file;
} else {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'composer install' . PHP_EOL
    );
}

// For loading recipes
set_include_path(__DIR__ . '/../..' . PATH_SEPARATOR . get_include_path());

define('DEPLOYER_BIN', __DIR__ . '/../../bin/dep');
define('__FIXTURES__', __DIR__ . '/../fixtures');
define('__REPOSITORY__', __DIR__ . '/../fixtures/repository');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer');

require_once __DIR__ . '/AbstractE2ETest.php';
