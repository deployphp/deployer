# Changelog

## 1.3.0 (2021-10-18)

*   Feature: Improve error reporting by appending previous exception messages.
    (#26 by @clue)

    For most common use cases this means that simply reporting the `Exception`
    message should give the most relevant details for any issues:

    ```php
    React\Promise\Stream\buffer($stream)->then(function (string $contents) {
        // …
    }, function (Exception $e) {
        echo 'Error:' . $e->getMessage() . PHP_EOL;
    });
    ```

*   Improve documentation, describe promise and stream data types.
    (#27 by @clue and #23 by @WyriHaximus)

*   Improve test suite and add `.gitattributes` to exclude dev files from exports.
    Use GitHub actions for continuous integration (CI) and run tests on PHPUnit 9 and PHP 8.
    (#21 by @reedy and #22, #24 and #25 by @SimonFrings)

## 1.2.0 (2019-07-03)

*   Feature: Support unwrapping object streams by buffering original write chunks in array.
    (#15 by @clue)

*   Feature: Clean up unneeded references for unwrapped streams when closing.
    (#18 by @clue)

*   Fix: Writing to closed unwrapped stream should return false (backpressure).
    (#17 by @clue)

*   Improve test suite to support PHPUnit 7, PHP 7.3 and fix incomplete test
    and improve API documentation.
    (#16 and #19 by @clue)

## 1.1.1 (2017-12-22)

*   Fix: Fix `all()` to assume null values if no event data is passed
    (#13 by @clue)

*   Improve test suite by simplifying test bootstrapping logic via Composer and
    add forward compatibility with PHPUnit 5 and PHPUnit 6 and
    test against PHP 7.1 and 7.2
    (#11 and #12 by @clue and #9 by @carusogabriel)

## 1.1.0 (2017-11-28)

* Feature: Reject `first()` when stream emits an error event
  (#7 by @clue)

* Fix: Explicit `close()` of unwrapped stream should not emit `error` event
  (#8 by @clue)

* Internal refactoring to simplify `buffer()` function
  (#6 by @kelunik)

## 1.0.0 (2017-10-24)

* First stable release, now following SemVer

> Contains no other changes, so it's actually fully compatible with the v0.1.2 release.

## 0.1.2 (2017-10-18)

* Feature: Optional maximum buffer length for `buffer()` (#3 by @WyriHaximus)
* Improvement: Readme improvements (#5 by @jsor)

## 0.1.1 (2017-05-15)

* Improvement: Forward compatibility with stream 1.0, 0.7, 0.6, and 0.5 (#2 by @WyriHaximus)

## 0.1.0 (2017-05-10)

* Initial release, adapted from [`clue/promise-stream-react`](https://github.com/clue/php-promise-stream-react)
