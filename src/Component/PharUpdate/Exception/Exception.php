<?php declare(strict_types=1);

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
     * @param mixed  $value,... The value(s).
     */
    public static function create(string $format, $value = null): self
    {
        if (0 < func_num_args()) {
            $format = vsprintf($format, array_slice(func_get_args(), 1));
        }

        return new static($format);
    }

    /**
     * Creates an exception for the last error message.
     */
    public static function lastError(): self
    {
        $error = error_get_last();

        return new static($error['message']);
    }
}
