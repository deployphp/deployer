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
        'You need to set up the project dependencies using the following commands:' . PHP_EOL
        . 'composer install' . PHP_EOL
    );
}

// For loading recipes
set_include_path(__DIR__ . '/..' . PATH_SEPARATOR . get_include_path());

putenv('DEPLOYER_LOCAL_WORKER=true');
require_once __DIR__ . '/constants.php';

require_once __DIR__ . '/spec/SpecTest.php';

// Init repository
$repository = __REPOSITORY__;

shell_exec("cd $repository && git init");
$branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
shell_exec("cd $repository && git checkout -B $branch 2>&1");
shell_exec("cd $repository && git add .");
shell_exec("cd $repository && git config user.name 'Anton Medvedev'");
shell_exec("cd $repository && git config user.email 'anton.medv@example.com'");
shell_exec("cd $repository && git commit -m 'first commit'");
