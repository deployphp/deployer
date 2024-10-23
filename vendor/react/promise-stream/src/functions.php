<?php

namespace React\Promise\Stream;

use Evenement\EventEmitterInterface;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Create a `Promise` which will be fulfilled with the stream data buffer.
 *
 * ```php
 * $stream = accessSomeJsonStream();
 *
 * React\Promise\Stream\buffer($stream)->then(function (string $contents) {
 *     var_dump(json_decode($contents));
 * });
 * ```
 *
 * The promise will be fulfilled with a `string` of all data chunks concatenated once the stream closes.
 *
 * The promise will be fulfilled with an empty `string` if the stream is already closed.
 *
 * The promise will be rejected with a `RuntimeException` if the stream emits an error.
 *
 * The promise will be rejected with a `RuntimeException` if it is cancelled.
 *
 * The optional `$maxLength` argument defaults to no limit. In case the maximum
 * length is given and the stream emits more data before the end, the promise
 * will be rejected with an `OverflowException`.
 *
 * ```php
 * $stream = accessSomeToLargeStream();
 *
 * React\Promise\Stream\buffer($stream, 1024)->then(function ($contents) {
 *     var_dump(json_decode($contents));
 * }, function ($error) {
 *     // Reaching here when the stream buffer goes above the max size,
 *     // in this example that is 1024 bytes,
 *     // or when the stream emits an error.
 * });
 * ```
 *
 * @param ReadableStreamInterface<string> $stream
 * @param ?int                            $maxLength Maximum number of bytes to buffer or null for unlimited.
 * @return PromiseInterface<string,\RuntimeException>
 */
function buffer(ReadableStreamInterface $stream, $maxLength = null)
{
    // stream already ended => resolve with empty buffer
    if (!$stream->isReadable()) {
        return Promise\resolve('');
    }

    $buffer = '';

    $promise = new Promise\Promise(function ($resolve, $reject) use ($stream, $maxLength, &$buffer, &$bufferer) {
        $bufferer = function ($data) use (&$buffer, $reject, $maxLength) {
            $buffer .= $data;

            if ($maxLength !== null && isset($buffer[$maxLength])) {
                $reject(new \OverflowException('Buffer exceeded maximum length'));
            }
        };

        $stream->on('data', $bufferer);

        $stream->on('error', function (\Exception $e) use ($reject) {
            $reject(new \RuntimeException(
                'An error occured on the underlying stream while buffering: ' . $e->getMessage(),
                $e->getCode(),
                $e
            ));
        });

        $stream->on('close', function () use ($resolve, &$buffer) {
            $resolve($buffer);
        });
    }, function ($_, $reject) {
        $reject(new \RuntimeException('Cancelled buffering'));
    });

    return $promise->then(null, function (\Exception $error) use (&$buffer, $bufferer, $stream) {
        // promise rejected => clear buffer and buffering
        $buffer = '';
        $stream->removeListener('data', $bufferer);

        throw $error;
    });
}

/**
 * Create a `Promise` which will be fulfilled once the given event triggers for the first time.
 *
 * ```php
 * $stream = accessSomeJsonStream();
 *
 * React\Promise\Stream\first($stream)->then(function (string $chunk) {
 *     echo 'The first chunk arrived: ' . $chunk;
 * });
 * ```
 *
 * The promise will be fulfilled with a `mixed` value of whatever the first event
 * emitted or `null` if the event does not pass any data.
 * If you do not pass a custom event name, then it will wait for the first "data"
 * event.
 * For common streams of type `ReadableStreamInterface<string>`, this means it will be
 * fulfilled with a `string` containing the first data chunk.
 *
 * The promise will be rejected with a `RuntimeException` if the stream emits an error
 * – unless you're waiting for the "error" event, in which case it will be fulfilled.
 *
 * The promise will be rejected with a `RuntimeException` once the stream closes
 * – unless you're waiting for the "close" event, in which case it will be fulfilled.
 *
 * The promise will be rejected with a `RuntimeException` if the stream is already closed.
 *
 * The promise will be rejected with a `RuntimeException` if it is cancelled.
 *
 * @param ReadableStreamInterface|WritableStreamInterface $stream
 * @param string                                          $event
 * @return PromiseInterface<mixed,\RuntimeException>
 */
function first(EventEmitterInterface $stream, $event = 'data')
{
    if ($stream instanceof ReadableStreamInterface) {
        // readable or duplex stream not readable => already closed
        // a half-open duplex stream is considered closed if its readable side is closed
        if (!$stream->isReadable()) {
            return Promise\reject(new \RuntimeException('Stream already closed'));
        }
    } elseif ($stream instanceof WritableStreamInterface) {
        // writable-only stream (not duplex) not writable => already closed
        if (!$stream->isWritable()) {
            return Promise\reject(new \RuntimeException('Stream already closed'));
        }
    }

    return new Promise\Promise(function ($resolve, $reject) use ($stream, $event, &$listener) {
        $listener = function ($data = null) use ($stream, $event, &$listener, $resolve) {
            $stream->removeListener($event, $listener);
            $resolve($data);
        };
        $stream->on($event, $listener);

        if ($event !== 'error') {
            $stream->on('error', function (\Exception $e) use ($stream, $event, $listener, $reject) {
                $stream->removeListener($event, $listener);
                $reject(new \RuntimeException(
                    'An error occured on the underlying stream while waiting for event: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                ));
            });
        }

        $stream->on('close', function () use ($stream, $event, $listener, $reject) {
            $stream->removeListener($event, $listener);
            $reject(new \RuntimeException('Stream closed'));
        });
    }, function ($_, $reject) use ($stream, $event, &$listener) {
        $stream->removeListener($event, $listener);
        $reject(new \RuntimeException('Operation cancelled'));
    });
}

/**
 * Create a `Promise` which will be fulfilled with an array of all the event data.
 *
 * ```php
 * $stream = accessSomeJsonStream();
 *
 * React\Promise\Stream\all($stream)->then(function (array $chunks) {
 *     echo 'The stream consists of ' . count($chunks) . ' chunk(s)';
 * });
 * ```
 *
 * The promise will be fulfilled with an `array` once the stream closes. The array
 * will contain whatever all events emitted or `null` values if the events do not pass any data.
 * If you do not pass a custom event name, then it will wait for all the "data"
 * events.
 * For common streams of type `ReadableStreamInterface<string>`, this means it will be
 * fulfilled with a `string[]` array containing all the data chunk.
 *
 * The promise will be fulfilled with an empty `array` if the stream is already closed.
 *
 * The promise will be rejected with a `RuntimeException` if the stream emits an error.
 *
 * The promise will be rejected with a `RuntimeException` if it is cancelled.
 *
 * @param ReadableStreamInterface|WritableStreamInterface $stream
 * @param string                                          $event
 * @return PromiseInterface<array,\RuntimeException>
 */
function all(EventEmitterInterface $stream, $event = 'data')
{
    // stream already ended => resolve with empty buffer
    if ($stream instanceof ReadableStreamInterface) {
        // readable or duplex stream not readable => already closed
        // a half-open duplex stream is considered closed if its readable side is closed
        if (!$stream->isReadable()) {
            return Promise\resolve(array());
        }
    } elseif ($stream instanceof WritableStreamInterface) {
        // writable-only stream (not duplex) not writable => already closed
        if (!$stream->isWritable()) {
            return Promise\resolve(array());
        }
    }

    $buffer = array();
    $bufferer = function ($data = null) use (&$buffer) {
        $buffer []= $data;
    };
    $stream->on($event, $bufferer);

    $promise = new Promise\Promise(function ($resolve, $reject) use ($stream, &$buffer) {
        $stream->on('error', function (\Exception $e) use ($reject) {
            $reject(new \RuntimeException(
                'An error occured on the underlying stream while buffering: ' . $e->getMessage(),
                $e->getCode(),
                $e
            ));
        });

        $stream->on('close', function () use ($resolve, &$buffer) {
            $resolve($buffer);
        });
    }, function ($_, $reject) {
        $reject(new \RuntimeException('Cancelled buffering'));
    });

    return $promise->then(null, function ($error) use (&$buffer, $bufferer, $stream, $event) {
        // promise rejected => clear buffer and buffering
        $buffer = array();
        $stream->removeListener($event, $bufferer);

        throw $error;
    });
}

/**
 * Unwrap a `Promise` which will be fulfilled with a `ReadableStreamInterface<T>`.
 *
 * This function returns a readable stream instance (implementing `ReadableStreamInterface<T>`)
 * right away which acts as a proxy for the future promise resolution.
 * Once the given Promise will be fulfilled with a `ReadableStreamInterface<T>`, its
 * data will be piped to the output stream.
 *
 * ```php
 * //$promise = someFunctionWhichResolvesWithAStream();
 * $promise = startDownloadStream($uri);
 *
 * $stream = React\Promise\Stream\unwrapReadable($promise);
 *
 * $stream->on('data', function (string $data) {
 *     echo $data;
 * });
 *
 * $stream->on('end', function () {
 *     echo 'DONE';
 * });
 * ```
 *
 * If the given promise is either rejected or fulfilled with anything but an
 * instance of `ReadableStreamInterface`, then the output stream will emit
 * an `error` event and close:
 *
 * ```php
 * $promise = startDownloadStream($invalidUri);
 *
 * $stream = React\Promise\Stream\unwrapReadable($promise);
 *
 * $stream->on('error', function (Exception $error) {
 *     echo 'Error: ' . $error->getMessage();
 * });
 * ```
 *
 * The given `$promise` SHOULD be pending, i.e. it SHOULD NOT be fulfilled or rejected
 * at the time of invoking this function.
 * If the given promise is already settled and does not fulfill with an instance of
 * `ReadableStreamInterface`, then you will not be able to receive the `error` event.
 *
 * You can `close()` the resulting stream at any time, which will either try to
 * `cancel()` the pending promise or try to `close()` the underlying stream.
 *
 * ```php
 * $promise = startDownloadStream($uri);
 *
 * $stream = React\Promise\Stream\unwrapReadable($promise);
 *
 * $loop->addTimer(2.0, function () use ($stream) {
 *     $stream->close();
 * });
 * ```
 *
 * @param PromiseInterface<ReadableStreamInterface<T>,\Exception> $promise
 * @return ReadableStreamInterface<T>
 */
function unwrapReadable(PromiseInterface $promise)
{
    return new UnwrapReadableStream($promise);
}

/**
 * unwrap a `Promise` which will be fulfilled with a `WritableStreamInterface<T>`.
 *
 * This function returns a writable stream instance (implementing `WritableStreamInterface<T>`)
 * right away which acts as a proxy for the future promise resolution.
 * Any writes to this instance will be buffered in memory for when the promise will
 * be fulfilled.
 * Once the given Promise will be fulfilled with a `WritableStreamInterface<T>`, any
 * data you have written to the proxy will be forwarded transparently to the inner
 * stream.
 *
 * ```php
 * //$promise = someFunctionWhichResolvesWithAStream();
 * $promise = startUploadStream($uri);
 *
 * $stream = React\Promise\Stream\unwrapWritable($promise);
 *
 * $stream->write('hello');
 * $stream->end('world');
 *
 * $stream->on('close', function () {
 *     echo 'DONE';
 * });
 * ```
 *
 * If the given promise is either rejected or fulfilled with anything but an
 * instance of `WritableStreamInterface`, then the output stream will emit
 * an `error` event and close:
 *
 * ```php
 * $promise = startUploadStream($invalidUri);
 *
 * $stream = React\Promise\Stream\unwrapWritable($promise);
 *
 * $stream->on('error', function (Exception $error) {
 *     echo 'Error: ' . $error->getMessage();
 * });
 * ```
 *
 * The given `$promise` SHOULD be pending, i.e. it SHOULD NOT be fulfilled or rejected
 * at the time of invoking this function.
 * If the given promise is already settled and does not fulfill with an instance of
 * `WritableStreamInterface`, then you will not be able to receive the `error` event.
 *
 * You can `close()` the resulting stream at any time, which will either try to
 * `cancel()` the pending promise or try to `close()` the underlying stream.
 *
 * ```php
 * $promise = startUploadStream($uri);
 *
 * $stream = React\Promise\Stream\unwrapWritable($promise);
 *
 * $loop->addTimer(2.0, function () use ($stream) {
 *     $stream->close();
 * });
 * ```
 *
 * @param PromiseInterface<WritableStreamInterface<T>,\Exception> $promise
 * @return WritableStreamInterface<T>
 */
function unwrapWritable(PromiseInterface $promise)
{
    return new UnwrapWritableStream($promise);
}
