<?php

namespace Fig\Http\Message;

/**
 * Defines constants for common HTTP request methods.
 *
 * Usage:
 *
 * <code>
 * class RequestFactory implements RequestMethodInterface
 * {
 *     public static function factory(
 *         $uri = '/',
 *         $method = self::METHOD_GET,
 *         $data = []
 *     ) {
 *     }
 * }
 * </code>
 */
interface RequestMethodInterface
{
    public const METHOD_HEAD    = 'HEAD';
    public const METHOD_GET     = 'GET';
    public const METHOD_POST    = 'POST';
    public const METHOD_PUT     = 'PUT';
    public const METHOD_PATCH   = 'PATCH';
    public const METHOD_DELETE  = 'DELETE';
    public const METHOD_PURGE   = 'PURGE';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_TRACE   = 'TRACE';
    public const METHOD_CONNECT = 'CONNECT';
}
