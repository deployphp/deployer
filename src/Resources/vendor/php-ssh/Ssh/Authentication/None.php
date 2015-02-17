<?php

namespace Ssh\Authentication;

use Ssh\Authentication;

/**
 * Username based authentication
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class None implements Authentication
{
    protected $username;

    /**
     * Constructor
     *
     * @param  string $username The authentication username
     */
    public function __construct($username)
    {
        $this->username = $username;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($session)
    {
        return true === ssh2_auth_none($session, $this->username);
    }
}
