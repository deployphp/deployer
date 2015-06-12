<?php
// Search and include "autoload" file
$loaded = false;

foreach (array(__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php') as $file) {
    if (file_exists($file)) {
        require $file;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

// Include common files
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/src/Helper/DeployerHelper.php';
require_once __DIR__ . '/recipe/Helper/RecipeTester.php';
