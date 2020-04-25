<?php

namespace Deployer\Component\PharUpdate\Exception;

/**
 * Provides additional functional to the Exception class.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Exception extends \Exception implements ExceptionInterface
{
    /**
     * Creates a new exception using a format and values.
     *
     * @param string $format    The format.
     * @param mixed  $value,... The value(s).
     *
     * @return Exception The exception.
     */
    public static function create($format, $value = null)
    {
        if (0 < func_num_args()) {
            $format = vsprintf($format, array_slice(func_get_args(), 1));
        }

        return new static($format);
    }

    /**
     * Creates an exception for the last error message.
     *
     * @return Exception The exception.
     */
    public static function lastError()
    {
        $error = error_get_last();

        return new static($error['message']);
    }
}
