<?php

namespace React\Http\Io;

use React\EventLoop\LoopInterface;

/**
 * [internal] Clock source that returns current timestamp and memoize clock for same tick
 *
 * This is mostly used as an internal optimization to avoid unneeded syscalls to
 * get the current system time multiple times within the same loop tick. For the
 * purpose of the HTTP server, the clock is assumed to not change to a
 * significant degree within the same loop tick. If you need a high precision
 * clock source, you may want to use `\hrtime()` instead (PHP 7.3+).
 *
 * The API is modelled to resemble the PSR-20 `ClockInterface` (in draft at the
 * time of writing this), but uses a `float` return value for performance
 * reasons instead.
 *
 * Note that this is an internal class only and nothing you should usually care
 * about for outside use.
 *
 * @internal
 */
class Clock
{
    /** @var LoopInterface $loop */
    private $loop;

    /** @var ?float */
    private $now;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /** @return float */
    public function now()
    {
        if ($this->now === null) {
            $this->now = \microtime(true);

            // remember clock for current loop tick only and update on next tick
            $now =& $this->now;
            $this->loop->futureTick(function () use (&$now) {
                assert($now !== null);
                $now = null;
            });
        }

        return $this->now;
    }
}
