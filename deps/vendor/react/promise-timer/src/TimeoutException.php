<?php

namespace React\Promise\Timer;

use RuntimeException;

class TimeoutException extends RuntimeException
{
    /** @var float */
    private $timeout;

    /**
     * @param float                      $timeout
     * @param string|null                $message
     * @param int|null                   $code
     * @param null|\Exception|\Throwable $previous
     */
    public function __construct($timeout, $message = '', $code = 0, $previous = null)
    {
        // Preserve compatibility with our former nullable signature, but avoid invalid arguments for the parent constructor:
        parent::__construct((string) $message, (int) $code, $previous);

        $this->timeout = (float) $timeout;
    }

    /**
     * Get the timeout value in seconds.
     *
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
