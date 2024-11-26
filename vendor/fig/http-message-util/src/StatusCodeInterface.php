<?php

namespace Fig\Http\Message;

/**
 * Defines constants for common HTTP status code.
 *
 * @see https://tools.ietf.org/html/rfc2295#section-8.1
 * @see https://tools.ietf.org/html/rfc2324#section-2.3
 * @see https://tools.ietf.org/html/rfc2518#section-9.7
 * @see https://tools.ietf.org/html/rfc2774#section-7
 * @see https://tools.ietf.org/html/rfc3229#section-10.4
 * @see https://tools.ietf.org/html/rfc4918#section-11
 * @see https://tools.ietf.org/html/rfc5842#section-7.1
 * @see https://tools.ietf.org/html/rfc5842#section-7.2
 * @see https://tools.ietf.org/html/rfc6585#section-3
 * @see https://tools.ietf.org/html/rfc6585#section-4
 * @see https://tools.ietf.org/html/rfc6585#section-5
 * @see https://tools.ietf.org/html/rfc6585#section-6
 * @see https://tools.ietf.org/html/rfc7231#section-6
 * @see https://tools.ietf.org/html/rfc7238#section-3
 * @see https://tools.ietf.org/html/rfc7725#section-3
 * @see https://tools.ietf.org/html/rfc7540#section-9.1.2
 * @see https://tools.ietf.org/html/rfc8297#section-2
 * @see https://tools.ietf.org/html/rfc8470#section-7
 * Usage:
 *
 * <code>
 * class ResponseFactory implements StatusCodeInterface
 * {
 *     public function createResponse($code = self::STATUS_OK)
 *     {
 *     }
 * }
 * </code>
 */
interface StatusCodeInterface
{
    // Informational 1xx
    public const STATUS_CONTINUE = 100;
    public const STATUS_SWITCHING_PROTOCOLS = 101;
    public const STATUS_PROCESSING = 102;
    public const STATUS_EARLY_HINTS = 103;
    // Successful 2xx
    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_ACCEPTED = 202;
    public const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_RESET_CONTENT = 205;
    public const STATUS_PARTIAL_CONTENT = 206;
    public const STATUS_MULTI_STATUS = 207;
    public const STATUS_ALREADY_REPORTED = 208;
    public const STATUS_IM_USED = 226;
    // Redirection 3xx
    public const STATUS_MULTIPLE_CHOICES = 300;
    public const STATUS_MOVED_PERMANENTLY = 301;
    public const STATUS_FOUND = 302;
    public const STATUS_SEE_OTHER = 303;
    public const STATUS_NOT_MODIFIED = 304;
    public const STATUS_USE_PROXY = 305;
    public const STATUS_RESERVED = 306;
    public const STATUS_TEMPORARY_REDIRECT = 307;
    public const STATUS_PERMANENT_REDIRECT = 308;
    // Client Errors 4xx
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_PAYMENT_REQUIRED = 402;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_METHOD_NOT_ALLOWED = 405;
    public const STATUS_NOT_ACCEPTABLE = 406;
    public const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const STATUS_REQUEST_TIMEOUT = 408;
    public const STATUS_CONFLICT = 409;
    public const STATUS_GONE = 410;
    public const STATUS_LENGTH_REQUIRED = 411;
    public const STATUS_PRECONDITION_FAILED = 412;
    public const STATUS_PAYLOAD_TOO_LARGE = 413;
    public const STATUS_URI_TOO_LONG = 414;
    public const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    public const STATUS_RANGE_NOT_SATISFIABLE = 416;
    public const STATUS_EXPECTATION_FAILED = 417;
    public const STATUS_IM_A_TEAPOT = 418;
    public const STATUS_MISDIRECTED_REQUEST = 421;
    public const STATUS_UNPROCESSABLE_ENTITY = 422;
    public const STATUS_LOCKED = 423;
    public const STATUS_FAILED_DEPENDENCY = 424;
    public const STATUS_TOO_EARLY = 425;
    public const STATUS_UPGRADE_REQUIRED = 426;
    public const STATUS_PRECONDITION_REQUIRED = 428;
    public const STATUS_TOO_MANY_REQUESTS = 429;
    public const STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const STATUS_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    // Server Errors 5xx
    public const STATUS_INTERNAL_SERVER_ERROR = 500;
    public const STATUS_NOT_IMPLEMENTED = 501;
    public const STATUS_BAD_GATEWAY = 502;
    public const STATUS_SERVICE_UNAVAILABLE = 503;
    public const STATUS_GATEWAY_TIMEOUT = 504;
    public const STATUS_VERSION_NOT_SUPPORTED = 505;
    public const STATUS_VARIANT_ALSO_NEGOTIATES = 506;
    public const STATUS_INSUFFICIENT_STORAGE = 507;
    public const STATUS_LOOP_DETECTED = 508;
    public const STATUS_NOT_EXTENDED = 510;
    public const STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;
}
