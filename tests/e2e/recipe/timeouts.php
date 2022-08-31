<?php
namespace Deployer;

// We need to user require instead of require_once,
// as the hosts HAVE to be loaded multiple times.
use Deployer\Exception\TimeoutException;

require_once __DIR__ . '/hosts.php';

task('test:timeouts', function () {
    try {
        run("php -r 'while(true){}'", [ 'timeout' => 1 ] );
    } catch (TimeoutException $e) {
        $ps = run("if ps aux | grep '[p]hp -r while(true){}'; then echo still running; else echo +timeout; fi");
        if ($ps != '+timeout') {
            throw new \Exception('Process still running.');
        }
    }
});

