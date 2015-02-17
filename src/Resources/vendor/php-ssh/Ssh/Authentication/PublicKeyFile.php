<?php

namespace Ssh\Authentication;

use Ssh\Authentication;

/**
 * Public key file authentication
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class PublicKeyFile implements Authentication
{
    protected $username;
    protected $publicKeyFile;
    protected $privateKeyFile;
    protected $passPhrase;

    /**
     * Constructor
     *
     * @param  string $username       The authentication username
     * @param  string $publicKeyFile  The path of the public key file
     * @param  string $privateKeyFile The path of the private key file
     * @param  string $passPhrase     An optional pass phrase for the key
     */
    public function __construct($username, $publicKeyFile, $privateKeyFile, $passPhrase = null)
    {
        $this->username = $username;
        $this->publicKeyFile = $publicKeyFile;
        $this->privateKeyFile = $privateKeyFile;
        $this->passPhrase = $passPhrase;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($session)
    {
        return ssh2_auth_pubkey_file(
            $session,
            $this->username,
            $this->publicKeyFile,
            $this->privateKeyFile,
            $this->passPhrase
        );
    }
}
