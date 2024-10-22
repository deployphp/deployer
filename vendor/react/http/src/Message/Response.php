<?php

namespace React\Http\Message;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Io\BufferedBody;
use React\Http\Io\HttpBodyStream;
use React\Stream\ReadableStreamInterface;
use RingCentral\Psr7\Response as Psr7Response;

/**
 * Represents an outgoing server response message.
 *
 * ```php
 * $response = new React\Http\Message\Response(
 *     React\Http\Message\Response::STATUS_OK,
 *     array(
 *         'Content-Type' => 'text/html'
 *     ),
 *     "<html>Hello world!</html>\n"
 * );
 * ```
 *
 * This class implements the
 * [PSR-7 `ResponseInterface`](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
 * which in turn extends the
 * [PSR-7 `MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface).
 *
 * On top of this, this class implements the
 * [PSR-7 Message Util `StatusCodeInterface`](https://github.com/php-fig/http-message-util/blob/master/src/StatusCodeInterface.php)
 * which means that most common HTTP status codes are available as class
 * constants with the `STATUS_*` prefix. For instance, the `200 OK` and
 * `404 Not Found` status codes can used as `Response::STATUS_OK` and
 * `Response::STATUS_NOT_FOUND` respectively.
 *
 * > Internally, this implementation builds on top of an existing incoming
 *   response message and only adds required streaming support. This base class is
 *   considered an implementation detail that may change in the future.
 *
 * @see \Psr\Http\Message\ResponseInterface
 */
final class Response extends Psr7Response implements StatusCodeInterface
{
    /**
     * Create an HTML response
     *
     * ```php
     * $html = <<<HTML
     * <!doctype html>
     * <html>
     * <body>Hello wörld!</body>
     * </html>
     *
     * HTML;
     *
     * $response = React\Http\Message\Response::html($html);
     * ```
     *
     * This is a convenient shortcut method that returns the equivalent of this:
     *
     * ```
     * $response = new React\Http\Message\Response(
     *     React\Http\Message\Response::STATUS_OK,
     *     [
     *         'Content-Type' => 'text/html; charset=utf-8'
     *     ],
     *     $html
     * );
     * ```
     *
     * This method always returns a response with a `200 OK` status code and
     * the appropriate `Content-Type` response header for the given HTTP source
     * string encoded in UTF-8 (Unicode). It's generally recommended to end the
     * given plaintext string with a trailing newline.
     *
     * If you want to use a different status code or custom HTTP response
     * headers, you can manipulate the returned response object using the
     * provided PSR-7 methods or directly instantiate a custom HTTP response
     * object using the `Response` constructor:
     *
     * ```php
     * $response = React\Http\Message\Response::html(
     *     "<h1>Error</h1>\n<p>Invalid user name given.</p>\n"
     * )->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
     * ```
     *
     * @param string $html
     * @return self
     */
    public static function html($html)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'text/html; charset=utf-8'), $html);
    }

    /**
     * Create a JSON response
     *
     * ```php
     * $response = React\Http\Message\Response::json(['name' => 'Alice']);
     * ```
     *
     * This is a convenient shortcut method that returns the equivalent of this:
     *
     * ```
     * $response = new React\Http\Message\Response(
     *     React\Http\Message\Response::STATUS_OK,
     *     [
     *         'Content-Type' => 'application/json'
     *     ],
     *     json_encode(
     *         ['name' => 'Alice'],
     *         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
     *     ) . "\n"
     * );
     * ```
     *
     * This method always returns a response with a `200 OK` status code and
     * the appropriate `Content-Type` response header for the given structured
     * data encoded as a JSON text.
     *
     * The given structured data will be encoded as a JSON text. Any `string`
     * values in the data must be encoded in UTF-8 (Unicode). If the encoding
     * fails, this method will throw an `InvalidArgumentException`.
     *
     * By default, the given structured data will be encoded with the flags as
     * shown above. This includes pretty printing (PHP 5.4+) and preserving
     * zero fractions for `float` values (PHP 5.6.6+) to ease debugging. It is
     * assumed any additional data overhead is usually compensated by using HTTP
     * response compression.
     *
     * If you want to use a different status code or custom HTTP response
     * headers, you can manipulate the returned response object using the
     * provided PSR-7 methods or directly instantiate a custom HTTP response
     * object using the `Response` constructor:
     *
     * ```php
     * $response = React\Http\Message\Response::json(
     *     ['error' => 'Invalid user name given']
     * )->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
     * ```
     *
     * @param mixed $data
     * @return self
     * @throws \InvalidArgumentException when encoding fails
     */
    public static function json($data)
    {
        $json = @\json_encode(
            $data,
            (\defined('JSON_PRETTY_PRINT') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE : 0) | (\defined('JSON_PRESERVE_ZERO_FRACTION') ? \JSON_PRESERVE_ZERO_FRACTION : 0)
        );

        // throw on error, now `false` but used to be `(string) "null"` before PHP 5.5
        if ($json === false || (\PHP_VERSION_ID < 50500 && \json_last_error() !== \JSON_ERROR_NONE)) {
            throw new \InvalidArgumentException(
                'Unable to encode given data as JSON' . (\function_exists('json_last_error_msg') ? ': ' . \json_last_error_msg() : ''),
                \json_last_error()
            );
        }

        return new self(self::STATUS_OK, array('Content-Type' => 'application/json'), $json . "\n");
    }

    /**
     * Create a plaintext response
     *
     * ```php
     * $response = React\Http\Message\Response::plaintext("Hello wörld!\n");
     * ```
     *
     * This is a convenient shortcut method that returns the equivalent of this:
     *
     * ```
     * $response = new React\Http\Message\Response(
     *     React\Http\Message\Response::STATUS_OK,
     *     [
     *         'Content-Type' => 'text/plain; charset=utf-8'
     *     ],
     *     "Hello wörld!\n"
     * );
     * ```
     *
     * This method always returns a response with a `200 OK` status code and
     * the appropriate `Content-Type` response header for the given plaintext
     * string encoded in UTF-8 (Unicode). It's generally recommended to end the
     * given plaintext string with a trailing newline.
     *
     * If you want to use a different status code or custom HTTP response
     * headers, you can manipulate the returned response object using the
     * provided PSR-7 methods or directly instantiate a custom HTTP response
     * object using the `Response` constructor:
     *
     * ```php
     * $response = React\Http\Message\Response::plaintext(
     *     "Error: Invalid user name given.\n"
     * )->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
     * ```
     *
     * @param string $text
     * @return self
     */
    public static function plaintext($text)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'text/plain; charset=utf-8'), $text);
    }

    /**
     * Create an XML response
     *
     * ```php
     * $xml = <<<XML
     * <?xml version="1.0" encoding="utf-8"?>
     * <body>
     *     <greeting>Hello wörld!</greeting>
     * </body>
     *
     * XML;
     *
     * $response = React\Http\Message\Response::xml($xml);
     * ```
     *
     * This is a convenient shortcut method that returns the equivalent of this:
     *
     * ```
     * $response = new React\Http\Message\Response(
     *     React\Http\Message\Response::STATUS_OK,
     *     [
     *         'Content-Type' => 'application/xml'
     *     ],
     *     $xml
     * );
     * ```
     *
     * This method always returns a response with a `200 OK` status code and
     * the appropriate `Content-Type` response header for the given XML source
     * string. It's generally recommended to use UTF-8 (Unicode) and specify
     * this as part of the leading XML declaration and to end the given XML
     * source string with a trailing newline.
     *
     * If you want to use a different status code or custom HTTP response
     * headers, you can manipulate the returned response object using the
     * provided PSR-7 methods or directly instantiate a custom HTTP response
     * object using the `Response` constructor:
     *
     * ```php
     * $response = React\Http\Message\Response::xml(
     *     "<error><message>Invalid user name given.</message></error>\n"
     * )->withStatus(React\Http\Message\Response::STATUS_BAD_REQUEST);
     * ```
     *
     * @param string $xml
     * @return self
     */
    public static function xml($xml)
    {
        return new self(self::STATUS_OK, array('Content-Type' => 'application/xml'), $xml);
    }

    /**
     * @param int                                            $status  HTTP status code (e.g. 200/404), see `self::STATUS_*` constants
     * @param array<string,string|string[]>                  $headers additional response headers
     * @param string|ReadableStreamInterface|StreamInterface $body    response body
     * @param string                                         $version HTTP protocol version (e.g. 1.1/1.0)
     * @param ?string                                        $reason  custom HTTP response phrase
     * @throws \InvalidArgumentException for an invalid body
     */
    public function __construct(
        $status = self::STATUS_OK,
        array $headers = array(),
        $body = '',
        $version = '1.1',
        $reason = null
    ) {
        if (\is_string($body)) {
            $body = new BufferedBody($body);
        } elseif ($body instanceof ReadableStreamInterface && !$body instanceof StreamInterface) {
            $body = new HttpBodyStream($body, null);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Invalid response body given');
        }

        parent::__construct(
            $status,
            $headers,
            $body,
            $version,
            $reason
        );
    }
}
