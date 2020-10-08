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

require_once __DIR__ . '/AbstractTest.php';

// Init repository
$repository = __DIR__ . '/fixtures/repository';
define('__REPOSITORY__', $repository);
`cd $repository && git init`;
$branch = trim(`git rev-parse --abbrev-ref HEAD`);
`cd $repository && git checkout -B $branch 2>&1`;
`cd $repository && git add .`;
`cd $repository && git config user.name 'Anton Medvedev'`;
`cd $repository && git config user.email 'anton.medv@example.com'`;
`cd $repository && git commit -m 'first commit'`;


putenv('DEPLOYER_LOCAL_WORKER=true');
define('DEPLOYER_BIN', __DIR__ . '/../bin/dep');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer');
