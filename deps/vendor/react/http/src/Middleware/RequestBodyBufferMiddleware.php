<?php

namespace React\Http\Middleware;

use OverflowException;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Io\BufferedBody;
use React\Http\Io\IniUtil;
use React\Promise\Promise;
use React\Stream\ReadableStreamInterface;

final class RequestBodyBufferMiddleware
{
    private $sizeLimit;

    /**
     * @param int|string|null $sizeLimit Either an int with the max request body size
     *                                   in bytes or an ini like size string
     *                                   or null to use post_max_size from PHP's
     *                                   configuration. (Note that the value from
     *                                   the CLI configuration will be used.)
     */
    public function __construct($sizeLimit = null)
    {
        if ($sizeLimit === null) {
            $sizeLimit = \ini_get('post_max_size');
        }

        $this->sizeLimit = IniUtil::iniSizeToBytes($sizeLimit);
    }

    public function __invoke(ServerRequestInterface $request, $next)
    {
        $body = $request->getBody();
        $size = $body->getSize();

        // happy path: skip if body is known to be empty (or is already buffered)
        if ($size === 0 || !$body instanceof ReadableStreamInterface || !$body->isReadable()) {
            // replace with empty body if body is streaming (or buffered size exceeds limit)
            if ($body instanceof ReadableStreamInterface || $size > $this->sizeLimit) {
                $request = $request->withBody(new BufferedBody(''));
            }

            return $next($request);
        }

        // request body of known size exceeding limit
        $sizeLimit = $this->sizeLimit;
        if ($size > $this->sizeLimit) {
            $sizeLimit = 0;
        }

        /** @var ?\Closure $closer */
        $closer = null;

        return new Promise(function ($resolve, $reject) use ($body, &$closer, $sizeLimit, $request, $next) {
            // buffer request body data in memory, discard but keep buffering if limit is reached
            $buffer = '';
            $bufferer = null;
            $body->on('data', $bufferer = function ($data) use (&$buffer, $sizeLimit, $body, &$bufferer) {
                $buffer .= $data;

                // On buffer overflow keep the request body stream in,
                // but ignore the contents and wait for the close event
                // before passing the request on to the next middleware.
                if (isset($buffer[$sizeLimit])) {
                    assert($bufferer instanceof \Closure);
                    $body->removeListener('data', $bufferer);
                    $bufferer = null;
                    $buffer = '';
                }
            });

            // call $next with current buffer and resolve or reject with its results
            $body->on('close', $closer = function () use (&$buffer, $request, $resolve, $reject, $next) {
                try {
                    // resolve with result of next handler
                    $resolve($next($request->withBody(new BufferedBody($buffer))));
                } catch (\Exception $e) {
                    $reject($e);
                } catch (\Throwable $e) { // @codeCoverageIgnoreStart
                    // reject Errors just like Exceptions (PHP 7+)
                    $reject($e); // @codeCoverageIgnoreEnd
                }
            });

            // reject buffering if body emits error
            $body->on('error', function (\Exception $e) use ($reject, $body, $closer) {
                // remove close handler to avoid resolving, then close and reject
                assert($closer instanceof \Closure);
                $body->removeListener('close', $closer);
                $body->close();

                $reject(new \RuntimeException(
                    'Error while buffering request body: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                ));
            });
        }, function () use ($body, &$closer) {
            // cancelled buffering: remove close handler to avoid resolving, then close and reject
            assert($closer instanceof \Closure);
            $body->removeListener('close', $closer);
            $body->close();

            throw new \RuntimeException('Cancelled buffering request body');
        });
    }
}
