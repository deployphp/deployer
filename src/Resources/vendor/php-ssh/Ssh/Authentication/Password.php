<?php

namespace Ssh\Authentication;

use Ssh\Authentication;

/**
 * Password Authentication
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Password implements Authentication
{
    protected $username;
    protected $password;

    /**
     * Constructor
     *
     * @param  string $username The authentication username
     * @param  string $password The authentication password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($session)
    {
        // This function generates a undocumented warning on authentification failure.
        return @ssh2_auth_password($session, $this->username, $this->password);
    }
}
