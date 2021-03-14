<?php declare(strict_types=1);
namespace Deployer;

use Deployer\Exception\TimeoutException;

task('verification', [
    'verification:timeouts'
]);

task('verification:timeouts', function () {
    try {
        run("php -r 'while(true){}'", timeout: 1);
    } catch (TimeoutException $e) {
        $ps = run("if ps aux | grep '[p]hp -r while(true){}'; then echo still running; else echo ok; fi");
        if ($ps != 'ok') {
            throw new \Exception('Process still running.');
        }
    }
});
