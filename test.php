<?php
/**
 * Dummy test file for quikly testing Deployer without needing to build it. Useful when debugging configurations.
 * Make sure you are pointing at the correct deploy file, update line 25.
 *
 * Usage: php /path/to/this/file.php [command] [stage]
 * Make sure you have installed all required dependencies using composer.
 */
include 'vendor/autoload.php';

use Deployer\Deployer;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Application;

$app = new Application();
$input = new ArgvInput();
$output = new ConsoleOutput();

include 'src/functions.php';

$deployer = new Deployer($app, $input, $output);

/* !! Update this path !! */
require '/path/to/deploy.php';

$deployer->run();