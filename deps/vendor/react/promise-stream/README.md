# PromiseStream

[![CI status](https://github.com/reactphp/promise-stream/workflows/CI/badge.svg)](https://github.com/reactphp/promise-stream/actions)

The missing link between Promise-land and Stream-land
for [ReactPHP](https://reactphp.org/).

**Table of Contents**

* [Usage](#usage)
    * [buffer()](#buffer)
    * [first()](#first)
    * [all()](#all)
    * [unwrapReadable()](#unwrapreadable)
    * [unwrapWritable()](#unwrapwritable)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Usage

This lightweight library consists only of a few simple functions.
All functions reside under the `React\Promise\Stream` namespace.

The below examples refer to all functions with their fully-qualified names like this:

```php
React\Promise\Stream\buffer(…);
```

As of PHP 5.6+ you can also import each required function into your code like this:

```php
use function React\Promise\Stream\buffer;

buffer(…);
```

Alternatively, you can also use an import statement similar to this:

```php
use React\Promise\Stream;

Stream\buffer(…);
```

### buffer()

The `buffer(ReadableStreamInterface<string> $stream, ?int $maxLength = null): PromiseInterface<string,RuntimeException>` function can be used to
create a `Promise` which will be fulfilled with the stream data buffer.

```php
$stream = accessSomeJsonStream();

React\Promise\Stream\buffer($stream)->then(function (string $contents) {
    var_dump(json_decode($contents));
});
```

The promise will be fulfilled with a `string` of all data chunks concatenated once the stream closes.

The promise will be fulfilled with an empty `string` if the stream is already closed.

The promise will be rejected with a `RuntimeException` if the stream emits an error.

The promise will be rejected with a `RuntimeException` if it is cancelled.

The optional `$maxLength` argument defaults to no limit. In case the maximum
length is given and the stream emits more data before the end, the promise
will be rejected with an `OverflowException`.

```php
$stream = accessSomeToLargeStream();

React\Promise\Stream\buffer($stream, 1024)->then(function ($contents) {
    var_dump(json_decode($contents));
}, function ($error) {
    // Reaching here when the stream buffer goes above the max size,
    // in this example that is 1024 bytes,
    // or when the stream emits an error.
});
```

### first()

The `first(ReadableStreamInterface|WritableStreamInterface $stream, string $event = 'data'): PromiseInterface<mixed,RuntimeException>` function can be used to
create a `Promise` which will be fulfilled once the given event triggers for the first time.

```php
$stream = accessSomeJsonStream();

React\Promise\Stream\first($stream)->then(function (string $chunk) {
    echo 'The first chunk arrived: ' . $chunk;
});
```

The promise will be fulfilled with a `mixed` value of whatever the first event
emitted or `null` if the event does not pass any data.
If you do not pass a custom event name, then it will wait for the first "data"
event.
For common streams of type `ReadableStreamInterface<string>`, this means it will be
fulfilled with a `string` containing the first data chunk.

The promise will be rejected with a `RuntimeException` if the stream emits an error
– unless you're waiting for the "error" event, in which case it will be fulfilled.

The promise will be rejected with a `RuntimeException` once the stream closes
– unless you're waiting for the "close" event, in which case it will be fulfilled.

The promise will be rejected with a `RuntimeException` if the stream is already closed.

The promise will be rejected with a `RuntimeException` if it is cancelled.

### all()

The `all(ReadableStreamInterface|WritableStreamInterface $stream, string $event = 'data'): PromiseInterface<array,RuntimeException>` function can be used to
create a `Promise` which will be fulfilled with an array of all the event data.

```php
$stream = accessSomeJsonStream();

React\Promise\Stream\all($stream)->then(function (array $chunks) {
    echo 'The stream consists of ' . count($chunks) . ' chunk(s)';
});
```

The promise will be fulfilled with an `array` once the stream closes. The array
will contain whatever all events emitted or `null` values if the events do not pass any data.
If you do not pass a custom event name, then it will wait for all the "data"
events.
For common streams of type `ReadableStreamInterface<string>`, this means it will be
fulfilled with a `string[]` array containing all the data chunk.

The promise will be fulfilled with an empty `array` if the stream is already closed.

The promise will be rejected with a `RuntimeException` if the stream emits an error.

The promise will be rejected with a `RuntimeException` if it is cancelled.

### unwrapReadable()

The `unwrapReadable(PromiseInterface<ReadableStreamInterface<T>,Exception> $promise): ReadableStreamInterface<T>` function can be used to
unwrap a `Promise` which will be fulfilled with a `ReadableStreamInterface<T>`.

This function returns a readable stream instance (implementing `ReadableStreamInterface<T>`)
right away which acts as a proxy for the future promise resolution.
Once the given Promise will be fulfilled with a `ReadableStreamInterface<T>`, its
data will be piped to the output stream.

```php
//$promise = someFunctionWhichResolvesWithAStream();
$promise = startDownloadStream($uri);

$stream = React\Promise\Stream\unwrapReadable($promise);

$stream->on('data', function (string $data) {
    echo $data;
});

$stream->on('end', function () {
    echo 'DONE';
});
```

If the given promise is either rejected or fulfilled with anything but an
instance of `ReadableStreamInterface`, then the output stream will emit
an `error` event and close:

```php
$promise = startDownloadStream($invalidUri);

$stream = React\Promise\Stream\unwrapReadable($promise);

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage();
});
```

The given `$promise` SHOULD be pending, i.e. it SHOULD NOT be fulfilled or rejected
at the time of invoking this function.
If the given promise is already settled and does not fulfill with an instance of
`ReadableStreamInterface`, then you will not be able to receive the `error` event.

You can `close()` the resulting stream at any time, which will either try to
`cancel()` the pending promise or try to `close()` the underlying stream.

```php
$promise = startDownloadStream($uri);

$stream = React\Promise\Stream\unwrapReadable($promise);

$loop->addTimer(2.0, function () use ($stream) {
    $stream->close();
});
```

### unwrapWritable()

The `unwrapWritable(PromiseInterface<WritableStreamInterface<T>,Exception> $promise): WritableStreamInterface<T>` function can be used to
unwrap a `Promise` which will be fulfilled with a `WritableStreamInterface<T>`.

This function returns a writable stream instance (implementing `WritableStreamInterface<T>`)
right away which acts as a proxy for the future promise resolution.
Any writes to this instance will be buffered in memory for when the promise will
be fulfilled.
Once the given Promise will be fulfilled with a `WritableStreamInterface<T>`, any
data you have written to the proxy will be forwarded transparently to the inner
stream.

```php
//$promise = someFunctionWhichResolvesWithAStream();
$promise = startUploadStream($uri);

$stream = React\Promise\Stream\unwrapWritable($promise);

$stream->write('hello');
$stream->end('world');

$stream->on('close', function () {
    echo 'DONE';
});
```

If the given promise is either rejected or fulfilled with anything but an
instance of `WritableStreamInterface`, then the output stream will emit
an `error` event and close:

```php
$promise = startUploadStream($invalidUri);

$stream = React\Promise\Stream\unwrapWritable($promise);

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage();
});
```

The given `$promise` SHOULD be pending, i.e. it SHOULD NOT be fulfilled or rejected
at the time of invoking this function.
If the given promise is already settled and does not fulfill with an instance of
`WritableStreamInterface`, then you will not be able to receive the `error` event.

You can `close()` the resulting stream at any time, which will either try to
`cancel()` the pending promise or try to `close()` the underlying stream.

```php
$promise = startUploadStream($uri);

$stream = React\Promise\Stream\unwrapWritable($promise);

$loop->addTimer(2.0, function () use ($stream) {
    $stream->close();
});
```

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require react/promise-stream:^1.3
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
