<?php

namespace Ssh\Authentication;

use Ssh\Authentication;

/**
 * SSH Agent authentication
 *
 * @author Cam Spiers <camspiers@gmail.com>
 */
class Agent implements Authentication
{
    protected $username;

    /**
     * Constructor
     *
     * @param  string $username       The authentication username
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
        return ssh2_auth_agent(
            $session,
            $this->username
        );
    }
}
