<?php
$file = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($file)) {
    require_once $file;
} else {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'composer install' . PHP_EOL
    );
}

require_once __DIR__ . '/ConsoleApplicationTester.php';
require_once __DIR__ . '/AbstractE2ETest.php';
