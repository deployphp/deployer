<?php

namespace Ssh\Authentication;

use Ssh\Authentication;

/**
 * Host based file authentication
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class HostBasedFile implements Authentication
{
    protected $username;
    protected $hostname;
    protected $publicKeyFile;
    protected $privateKeyFile;
    protected $passPhrase;
    protected $localUsername;

    /**
     * Constructor
     *
     * @param  string $username
     * @param  string $hostname
     * @param  string $publicKeyFile
     * @param  string $privateKeyFile
     * @param  string $passPhrase     An optional pass phrase for the key
     * @param  string $localUsername  An optional local usernale. If omitted,
     *                                the username will be used
     */
    public function __construct($username, $hostname, $publicKeyFile, $privateKeyFile, $passPhrase = null, $localUsername = null)
    {
        $this->username = $username;
        $this->hostname = $hostname;
        $this->publicKeyFile = $publicKeyFile;
        $this->privateKeyFile = $privateKeyFile;
        $this->passPhrase = $passPhrase;
        $this->localUsername = $localUsername;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($session)
    {
        return ssh2_auth_hostbased_file(
            $session,
            $this->username,
            $this->hostname,
            $this->publicKeyFile,
            $this->privateKeyFile,
            $this->passPhrase,
            $this->localUsername
        );
    }
}
