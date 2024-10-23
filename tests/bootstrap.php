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

// For loading recipes
set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

putenv('DEPLOYER_LOCAL_WORKER=true');
define('DEPLOYER_BIN', __DIR__ . '/../bin/dep');
define('__FIXTURES__', __DIR__ . '/fixtures');
define('__REPOSITORY__', __DIR__ . '/fixtures/repository');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer');

require_once __DIR__ . '/legacy/AbstractTest.php';
require_once __DIR__ . '/joy/JoyTest.php';

// Init repository
$repository = __REPOSITORY__;

`cd $repository && git init`;
$branch = trim(`git rev-parse --abbrev-ref HEAD`);
`cd $repository && git checkout -B $branch 2>&1`;
`cd $repository && git add .`;
`cd $repository && git config user.name 'Anton Medvedev'`;
`cd $repository && git config user.email 'anton.medv@example.com'`;
`cd $repository && git commit -m 'first commit'`;
