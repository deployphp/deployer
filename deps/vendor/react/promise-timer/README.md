# PromiseTimer

[![CI status](https://github.com/reactphp/promise-timer/workflows/CI/badge.svg)](https://github.com/reactphp/promise-timer/actions)

A trivial implementation of timeouts for `Promise`s, built on top of [ReactPHP](https://reactphp.org/).

**Table of contents**

* [Usage](#usage)
    * [timeout()](#timeout)
    * [sleep()](#sleep)
    * [~~resolve()~~](#resolve)
    * [~~reject()~~](#reject)
    * [TimeoutException](#timeoutexception)
        * [getTimeout()](#gettimeout)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Usage

This lightweight library consists only of a few simple functions.
All functions reside under the `React\Promise\Timer` namespace.

The below examples refer to all functions with their fully-qualified names like this:

```php
React\Promise\Timer\timeout(…);
```

As of PHP 5.6+ you can also import each required function into your code like this:

```php
use function React\Promise\Timer\timeout;

timeout(…);
```

Alternatively, you can also use an import statement similar to this:

```php
use React\Promise\Timer;

Timer\timeout(…);
```

### timeout()

The `timeout(PromiseInterface<mixed, Exception|mixed> $promise, float $time, ?LoopInterface $loop = null): PromiseInterface<mixed, TimeoutException|Exception|mixed>` function can be used to
cancel operations that take *too long*.

You need to pass in an input `$promise` that represents a pending operation
and timeout parameters. It returns a new promise with the following
resolution behavior:

- If the input `$promise` resolves before `$time` seconds, resolve the
  resulting promise with its fulfillment value.

- If the input `$promise` rejects before `$time` seconds, reject the
  resulting promise with its rejection value.

- If the input `$promise` does not settle before `$time` seconds, *cancel*
  the operation and reject the resulting promise with a [`TimeoutException`](#timeoutexception).

Internally, the given `$time` value will be used to start a timer that will
*cancel* the pending operation once it triggers. This implies that if you
pass a really small (or negative) value, it will still start a timer and will
thus trigger at the earliest possible time in the future.

If the input `$promise` is already settled, then the resulting promise will
resolve or reject immediately without starting a timer at all.

This function takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use. You can use a `null` value here in order to
use the [default loop](https://github.com/reactphp/event-loop#loop). This value
SHOULD NOT be given unless you're sure you want to explicitly use a given event
loop instance.

A common use case for handling only resolved values looks like this:

```php
$promise = accessSomeRemoteResource();
React\Promise\Timer\timeout($promise, 10.0)->then(function ($value) {
    // the operation finished within 10.0 seconds
});
```

A more complete example could look like this:

```php
$promise = accessSomeRemoteResource();
React\Promise\Timer\timeout($promise, 10.0)->then(
    function ($value) {
        // the operation finished within 10.0 seconds
    },
    function ($error) {
        if ($error instanceof React\Promise\Timer\TimeoutException) {
            // the operation has failed due to a timeout
        } else {
            // the input operation has failed due to some other error
        }
    }
);
```

Or if you're using [react/promise v2.2.0](https://github.com/reactphp/promise) or up:

```php
React\Promise\Timer\timeout($promise, 10.0)
    ->then(function ($value) {
        // the operation finished within 10.0 seconds
    })
    ->otherwise(function (React\Promise\Timer\TimeoutException $error) {
        // the operation has failed due to a timeout
    })
    ->otherwise(function ($error) {
        // the input operation has failed due to some other error
    })
;
```

As discussed above, the [`timeout()`](#timeout) function will take care of
the underlying operation if it takes *too long*. In this case, you can be
sure the resulting promise will always be rejected with a
[`TimeoutException`](#timeoutexception). On top of this, the function will
try to *cancel* the underlying operation. Responsibility for this
cancellation logic is left up to the underlying operation.

- A common use case involves cleaning up any resources like open network
  sockets or file handles or terminating external processes or timers.

- If the given input `$promise` does not support cancellation, then this is a
  NO-OP. This means that while the resulting promise will still be rejected,
  the underlying input `$promise` may still be pending and can hence continue
  consuming resources

On top of this, the returned promise is implemented in such a way that it can
be cancelled when it is still pending. Cancelling a pending promise will
cancel the underlying operation. As discussed above, responsibility for this
cancellation logic is left up to the underlying operation.

```php
$promise = accessSomeRemoteResource();
$timeout = React\Promise\Timer\timeout($promise, 10.0);

$timeout->cancel();
```

For more details on the promise cancellation, please refer to the
[Promise documentation](https://github.com/reactphp/promise#cancellablepromiseinterface).

If you want to wait for multiple promises to resolve, you can use the normal
promise primitives like this:

```php
$promises = array(
    accessSomeRemoteResource(),
    accessSomeRemoteResource(),
    accessSomeRemoteResource()
);

$promise = React\Promise\all($promises);

React\Promise\Timer\timeout($promise, 10)->then(function ($values) {
    // *all* promises resolved
});
```

The applies to all promise collection primitives alike, i.e. `all()`,
`race()`, `any()`, `some()` etc.

For more details on the promise primitives, please refer to the
[Promise documentation](https://github.com/reactphp/promise#functions).

### sleep()

The `sleep(float $time, ?LoopInterface $loop = null): PromiseInterface<void, RuntimeException>` function can be used to
create a new promise that resolves in `$time` seconds.

```php
React\Promise\Timer\sleep(1.5)->then(function () {
    echo 'Thanks for waiting!' . PHP_EOL;
});
```

Internally, the given `$time` value will be used to start a timer that will
resolve the promise once it triggers. This implies that if you pass a really
small (or negative) value, it will still start a timer and will thus trigger
at the earliest possible time in the future.

This function takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use. You can use a `null` value here in order to
use the [default loop](https://github.com/reactphp/event-loop#loop). This value
SHOULD NOT be given unless you're sure you want to explicitly use a given event
loop instance.

The returned promise is implemented in such a way that it can be cancelled
when it is still pending. Cancelling a pending promise will reject its value
with a `RuntimeException` and clean up any pending timers.

```php
$timer = React\Promise\Timer\sleep(2.0);

$timer->cancel();
```

### ~~resolve()~~

> Deprecated since v1.8.0, see [`sleep()`](#sleep) instead.

The `resolve(float $time, ?LoopInterface $loop = null): PromiseInterface<float, RuntimeException>` function can be used to
create a new promise that resolves in `$time` seconds with the `$time` as the fulfillment value.

```php
React\Promise\Timer\resolve(1.5)->then(function ($time) {
    echo 'Thanks for waiting ' . $time . ' seconds' . PHP_EOL;
});
```

Internally, the given `$time` value will be used to start a timer that will
resolve the promise once it triggers. This implies that if you pass a really
small (or negative) value, it will still start a timer and will thus trigger
at the earliest possible time in the future.

This function takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use. You can use a `null` value here in order to
use the [default loop](https://github.com/reactphp/event-loop#loop). This value
SHOULD NOT be given unless you're sure you want to explicitly use a given event
loop instance.

The returned promise is implemented in such a way that it can be cancelled
when it is still pending. Cancelling a pending promise will reject its value
with a `RuntimeException` and clean up any pending timers.

```php
$timer = React\Promise\Timer\resolve(2.0);

$timer->cancel();
```

### ~~reject()~~

> Deprecated since v1.8.0, see [`sleep()`](#sleep) instead.

The `reject(float $time, ?LoopInterface $loop = null): PromiseInterface<void, TimeoutException|RuntimeException>` function can be used to
create a new promise which rejects in `$time` seconds with a `TimeoutException`.

```php
React\Promise\Timer\reject(2.0)->then(null, function (React\Promise\Timer\TimeoutException $e) {
    echo 'Rejected after ' . $e->getTimeout() . ' seconds ' . PHP_EOL;
});
```

Internally, the given `$time` value will be used to start a timer that will
reject the promise once it triggers. This implies that if you pass a really
small (or negative) value, it will still start a timer and will thus trigger
at the earliest possible time in the future.

This function takes an optional `LoopInterface|null $loop` parameter that can be used to
pass the event loop instance to use. You can use a `null` value here in order to
use the [default loop](https://github.com/reactphp/event-loop#loop). This value
SHOULD NOT be given unless you're sure you want to explicitly use a given event
loop instance.

The returned promise is implemented in such a way that it can be cancelled
when it is still pending. Cancelling a pending promise will reject its value
with a `RuntimeException` and clean up any pending timers.

```php
$timer = React\Promise\Timer\reject(2.0);

$timer->cancel();
```

### TimeoutException

The `TimeoutException` extends PHP's built-in `RuntimeException`.


#### getTimeout()

The `getTimeout(): float` method can be used to
get the timeout value in seconds.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require react/promise-timer:^1.8
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+ and
HHVM.
It's *highly recommended to use the latest supported PHP version* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ vendor/bin/phpunit
```

## License

MIT, see [LICENSE file](LICENSE).
