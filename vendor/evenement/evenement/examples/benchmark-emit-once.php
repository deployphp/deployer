<?php declare(strict_types=1);

/*
 * This file is part of Evenement.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

ini_set('memory_limit', '512M');

const ITERATIONS = 100000;

use Evenement\EventEmitter;

require __DIR__.'/../vendor/autoload.php';

$emitter = new EventEmitter();

for ($i = 0; $i < ITERATIONS; $i++) {
    $emitter->once('event', function ($a, $b, $c) {});
}

$start = microtime(true);
$emitter->emit('event', [1, 2, 3]);
$time = microtime(true) - $start;

echo 'Emitting one event to ', number_format(ITERATIONS), ' once listeners took: ', number_format($time, 2), 's', PHP_EOL;
