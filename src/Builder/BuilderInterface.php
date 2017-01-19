<?php

namespace Deployer\Builder;

use Deployer\Server\Password\PasswordGetterInterface;

interface BuilderInterface
{
    /**
     * Define user name for authentication.
     *
     * @param string $name
     *
     * @return BuilderInterface
     */
    public function user($name);

    /**
     * Set password for connection
     *
     * @param string|null|PasswordGetterInterface $password If you did not define password it will be asked on connection.
     *
     * @return BuilderInterface
     */
    public function password($password = null);

    /**
     * If you use an ssh config file you can user it.
     *
     * @param string $file Config file path
     *
     * @return BuilderInterface
     */
    public function configFile($file = '~/.ssh/config');

    /**
     * Authenticate with public key
     *
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param string $passPhrase
     *
     * @return BuilderInterface
     */
    public function identityFile($publicKeyFile = '~/.ssh/id_rsa.pub', $privateKeyFile = '~/.ssh/id_rsa', $passPhrase = '');

    /**
     * @param string $pemFile
     * @return BuilderInterface
     */
    public function pemFile($pemFile);

    /**
     * Using forward agent to authentication
     *
     * @return BuilderInterface
     */
    public function forwardAgent();

    /**
     * @param string|array $stages
     * @return BuilderInterface
     */
    public function stage($stages);

    /**
     * Set server configuration.
     *
     * @param string           $name
     * @param array|int|string $value
     *
     * @return BuilderInterface
     */
    public function set($name, $value);

    /**
     * Use pty connection
     *
     * @param $pty
     * @return BuilderInterface
     */
    public function pty($pty);
}
