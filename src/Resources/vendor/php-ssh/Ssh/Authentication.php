<?php

namespace Ssh;

/**
 * Interface that must be implemented by the authentication classes
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
interface Authentication
{
    /**
     * Authenticates the given SSH session
     *
     * @param  resource $session
     *
     * @return Boolean TRUE on success, or FALSE on failure
     */
    function authenticate($session);
}
