<?php

namespace React\Promise\Timer;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Cancel operations that take *too long*.
 *
 * You need to pass in an input `$promise` that represents a pending operation
 * and timeout parameters. It returns a new promise with the following
 * resolution behavior:
 *
 * - If the input `$promise` resolves before `$time` seconds, resolve the
 *   resulting promise with its fulfillment value.
 *
 * - If the input `$promise` rejects before `$time` seconds, reject the
 *   resulting promise with its rejection value.
 *
 * - If the input `$promise` does not settle before `$time` seconds, *cancel*
 *   the operation and reject the resulting promise with a [`TimeoutException`](#timeoutexception).
 *
 * Internally, the given `$time` value will be used to start a timer that will
 * *cancel* the pending operation once it triggers. This implies that if you
 * pass a really small (or negative) value, it will still start a timer and will
 * thus trigger at the earliest possible time in the future.
 *
 * If the input `$promise` is already settled, then the resulting promise will
 * resolve or reject immediately without starting a timer at all.
 *
 * This function takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use. You can use a `null` value here in order to
 * use the [default loop](https://github.com/reactphp/event-loop#loop). This value
 * SHOULD NOT be given unless you're sure you want to explicitly use a given event
 * loop instance.
 *
 * A common use case for handling only resolved values looks like this:
 *
 * ```php
 * $promise = accessSomeRemoteResource();
 * React\Promise\Timer\timeout($promise, 10.0)->then(function ($value) {
 *     // the operation finished within 10.0 seconds
 * });
 * ```
 *
 * A more complete example could look like this:
 *
 * ```php
 * $promise = accessSomeRemoteResource();
 * React\Promise\Timer\timeout($promise, 10.0)->then(
 *     function ($value) {
 *         // the operation finished within 10.0 seconds
 *     },
 *     function ($error) {
 *         if ($error instanceof React\Promise\Timer\TimeoutException) {
 *             // the operation has failed due to a timeout
 *         } else {
 *             // the input operation has failed due to some other error
 *         }
 *     }
 * );
 * ```
 *
 * Or if you're using [react/promise v2.2.0](https://github.com/reactphp/promise) or up:
 *
 * ```php
 * React\Promise\Timer\timeout($promise, 10.0)
 *     ->then(function ($value) {
 *         // the operation finished within 10.0 seconds
 *     })
 *     ->otherwise(function (React\Promise\Timer\TimeoutException $error) {
 *         // the operation has failed due to a timeout
 *     })
 *     ->otherwise(function ($error) {
 *         // the input operation has failed due to some other error
 *     })
 * ;
 * ```
 *
 * As discussed above, the [`timeout()`](#timeout) function will take care of
 * the underlying operation if it takes *too long*. In this case, you can be
 * sure the resulting promise will always be rejected with a
 * [`TimeoutException`](#timeoutexception). On top of this, the function will
 * try to *cancel* the underlying operation. Responsibility for this
 * cancellation logic is left up to the underlying operation.
 *
 * - A common use case involves cleaning up any resources like open network
 *   sockets or file handles or terminating external processes or timers.
 *
 * - If the given input `$promise` does not support cancellation, then this is a
 *   NO-OP. This means that while the resulting promise will still be rejected,
 *   the underlying input `$promise` may still be pending and can hence continue
 *   consuming resources
 *
 * On top of this, the returned promise is implemented in such a way that it can
 * be cancelled when it is still pending. Cancelling a pending promise will
 * cancel the underlying operation. As discussed above, responsibility for this
 * cancellation logic is left up to the underlying operation.
 *
 * ```php
 * $promise = accessSomeRemoteResource();
 * $timeout = React\Promise\Timer\timeout($promise, 10.0);
 *
 * $timeout->cancel();
 * ```
 *
 * For more details on the promise cancellation, please refer to the
 * [Promise documentation](https://github.com/reactphp/promise#cancellablepromiseinterface).
 *
 * If you want to wait for multiple promises to resolve, you can use the normal
 * promise primitives like this:
 *
 * ```php
 * $promises = array(
 *     accessSomeRemoteResource(),
 *     accessSomeRemoteResource(),
 *     accessSomeRemoteResource()
 * );
 *
 * $promise = React\Promise\all($promises);
 *
 * React\Promise\Timer\timeout($promise, 10)->then(function ($values) {
 *     // *all* promises resolved
 * });
 * ```
 *
 * The applies to all promise collection primitives alike, i.e. `all()`,
 * `race()`, `any()`, `some()` etc.
 *
 * For more details on the promise primitives, please refer to the
 * [Promise documentation](https://github.com/reactphp/promise#functions).
 *
 * @param PromiseInterface<mixed, \Exception|mixed> $promise
 * @param float $time
 * @param ?LoopInterface $loop
 * @return PromiseInterface<mixed, TimeoutException|\Exception|mixed>
 */
function timeout(PromiseInterface $promise, $time, LoopInterface $loop = null)
{
    // cancelling this promise will only try to cancel the input promise,
    // thus leaving responsibility to the input promise.
    $canceller = null;
    if ($promise instanceof CancellablePromiseInterface || (!\interface_exists('React\Promise\CancellablePromiseInterface') && \method_exists($promise, 'cancel'))) {
        // pass promise by reference to clean reference after cancellation handler
        // has been invoked once in order to avoid garbage references in call stack.
        $canceller = function () use (&$promise) {
            $promise->cancel();
            $promise = null;
        };
    }

    if ($loop === null) {
        $loop = Loop::get();
    }

    return new Promise(function ($resolve, $reject) use ($loop, $time, $promise) {
        $timer = null;
        $promise = $promise->then(function ($v) use (&$timer, $loop, $resolve) {
            if ($timer) {
                $loop->cancelTimer($timer);
            }
            $timer = false;
            $resolve($v);
        }, function ($v) use (&$timer, $loop, $reject) {
            if ($timer) {
                $loop->cancelTimer($timer);
            }
            $timer = false;
            $reject($v);
        });

        // promise already resolved => no need to start timer
        if ($timer === false) {
            return;
        }

        // start timeout timer which will cancel the input promise
        $timer = $loop->addTimer($time, function () use ($time, &$promise, $reject) {
            $reject(new TimeoutException($time, 'Timed out after ' . $time . ' seconds'));

            // try to invoke cancellation handler of input promise and then clean
            // reference in order to avoid garbage references in call stack.
            if ($promise instanceof CancellablePromiseInterface || (!\interface_exists('React\Promise\CancellablePromiseInterface') && \method_exists($promise, 'cancel'))) {
                $promise->cancel();
            }
            $promise = null;
        });
    }, $canceller);
}

/**
 * Create a new promise that resolves in `$time` seconds.
 *
 * ```php
 * React\Promise\Timer\sleep(1.5)->then(function () {
 *     echo 'Thanks for waiting!' . PHP_EOL;
 * });
 * ```
 *
 * Internally, the given `$time` value will be used to start a timer that will
 * resolve the promise once it triggers. This implies that if you pass a really
 * small (or negative) value, it will still start a timer and will thus trigger
 * at the earliest possible time in the future.
 *
 * This function takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use. You can use a `null` value here in order to
 * use the [default loop](https://github.com/reactphp/event-loop#loop). This value
 * SHOULD NOT be given unless you're sure you want to explicitly use a given event
 * loop instance.
 *
 * The returned promise is implemented in such a way that it can be cancelled
 * when it is still pending. Cancelling a pending promise will reject its value
 * with a `RuntimeException` and clean up any pending timers.
 *
 * ```php
 * $timer = React\Promise\Timer\sleep(2.0);
 *
 * $timer->cancel();
 * ```
 *
 * @param float $time
 * @param ?LoopInterface $loop
 * @return PromiseInterface<void, \RuntimeException>
 */
function sleep($time, LoopInterface $loop = null)
{
    if ($loop === null) {
        $loop = Loop::get();
    }

    $timer = null;
    return new Promise(function ($resolve) use ($loop, $time, &$timer) {
        // resolve the promise when the timer fires in $time seconds
        $timer = $loop->addTimer($time, function () use ($resolve) {
            $resolve();
        });
    }, function () use (&$timer, $loop) {
        // cancelling this promise will cancel the timer, clean the reference
        // in order to avoid garbage references in call stack and then reject.
        $loop->cancelTimer($timer);
        $timer = null;

        throw new \RuntimeException('Timer cancelled');
    });
}

/**
 * [Deprecated] Create a new promise that resolves in `$time` seconds with the `$time` as the fulfillment value.
 *
 * ```php
 * React\Promise\Timer\resolve(1.5)->then(function ($time) {
 *     echo 'Thanks for waiting ' . $time . ' seconds' . PHP_EOL;
 * });
 * ```
 *
 * Internally, the given `$time` value will be used to start a timer that will
 * resolve the promise once it triggers. This implies that if you pass a really
 * small (or negative) value, it will still start a timer and will thus trigger
 * at the earliest possible time in the future.
 *
 * This function takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use. You can use a `null` value here in order to
 * use the [default loop](https://github.com/reactphp/event-loop#loop). This value
 * SHOULD NOT be given unless you're sure you want to explicitly use a given event
 * loop instance.
 *
 * The returned promise is implemented in such a way that it can be cancelled
 * when it is still pending. Cancelling a pending promise will reject its value
 * with a `RuntimeException` and clean up any pending timers.
 *
 * ```php
 * $timer = React\Promise\Timer\resolve(2.0);
 *
 * $timer->cancel();
 * ```
 *
 * @param float $time
 * @param ?LoopInterface $loop
 * @return PromiseInterface<float, \RuntimeException>
 * @deprecated 1.8.0 See `sleep()` instead
 * @see sleep()
 */
function resolve($time, LoopInterface $loop = null)
{
    return sleep($time, $loop)->then(function() use ($time) {
        return $time;
    });
}

/**
 * [Deprecated] Create a new promise which rejects in `$time` seconds with a `TimeoutException`.
 *
 * ```php
 * React\Promise\Timer\reject(2.0)->then(null, function (React\Promise\Timer\TimeoutException $e) {
 *     echo 'Rejected after ' . $e->getTimeout() . ' seconds ' . PHP_EOL;
 * });
 * ```
 *
 * Internally, the given `$time` value will be used to start a timer that will
 * reject the promise once it triggers. This implies that if you pass a really
 * small (or negative) value, it will still start a timer and will thus trigger
 * at the earliest possible time in the future.
 *
 * This function takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use. You can use a `null` value here in order to
 * use the [default loop](https://github.com/reactphp/event-loop#loop). This value
 * SHOULD NOT be given unless you're sure you want to explicitly use a given event
 * loop instance.
 *
 * The returned promise is implemented in such a way that it can be cancelled
 * when it is still pending. Cancelling a pending promise will reject its value
 * with a `RuntimeException` and clean up any pending timers.
 *
 * ```php
 * $timer = React\Promise\Timer\reject(2.0);
 *
 * $timer->cancel();
 * ```
 *
 * @param float         $time
 * @param LoopInterface $loop
 * @return PromiseInterface<void, TimeoutException|\RuntimeException>
 * @deprecated 1.8.0 See `sleep()` instead
 * @see sleep()
 */
function reject($time, LoopInterface $loop = null)
{
    return sleep($time, $loop)->then(function () use ($time) {
        throw new TimeoutException($time, 'Timer expired after ' . $time . ' seconds');
    });
}
