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
        'wget http://getcomposer.org/composer.phar' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

define('DEPLOYER', true);
define('DEPLOYER_BIN', __DIR__ . '/../bin/dep');
define('DEPLOYER_PARALLEL_PTY', false);
define('DEPLOYER_FIXTURES', __DIR__ . '/fixture');

require_once __DIR__ . '/recipe/tester.php';
