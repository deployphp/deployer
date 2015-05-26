<?php

// Search and include "autoload" file
$vendorDir = __DIR__ . '/../../..';

if (file_exists($file = $vendorDir . '/autoload.php')) {
    require_once $file;
} else if (file_exists($file = './vendor/autoload.php')) {
    require_once $file;
} else {
    throw new \RuntimeException("Not found composer autoload");
}

// Include common files
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/src/Helper/DeployerHelper.php';
require_once __DIR__ . '/recipe/Helper/RecipeTester.php';
