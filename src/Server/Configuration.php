<?php

/**
 * (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Server\Password\PasswordGetterInterface;

/**
 * Server configuration
 */
class Configuration
{
    const AUTH_BY_PASSWORD                      = 0;
    const AUTH_BY_CONFIG                        = 1;
    const AUTH_BY_IDENTITY_FILE                 = 2;
    const AUTH_BY_PEM_FILE                      = 3;
    const AUTH_BY_AGENT                         = 4;
    const AUTH_BY_IDENTITY_FILE_AND_PASSWORD    = 5;

    /**
     * Type of authentication.
     * By default try to connect via password authentication
     *
     * @var int
     */
    private $authenticationMethod = self::AUTH_BY_PASSWORD;

    /**
     * Server name
     *
     * @var string
     */
    private $name;

    /**
     * Server host.
     *
     * @var string
     */
    private $host;

    /**
     * Server port.
     *
     * @var int
     */
    private $port;

    /**
     * User of remote server.
     *
     * @var string
     */
    private $user;

    /**
     * Used for authentication with password.
     *
     * @var string
     */
    private $password;

    /**
     * Used for authentication with config file.
     *
     * @var string
     */
    private $configFile;

    /**
     * Used for authentication with public key.
     *
     * @var string
     */
    private $publicKey;

    /**
     * Used for authentication with public key.
     *
     * @var string
     */
    private $privateKey;

    /**
     * Used for authentication with public key.
     *
     * @var string
     */
    private $passPhrase;

    /**
     * Pem file.
     *
     * @var string
     */
    private $pemFile;

    /**
     * Pty configuration
     *
     * @var mixed
     */
    private $pty = null;


    /**
     * Construct
     *
     * @param string $name
     * @param string $host
     * @param int    $port
     */
    public function __construct($name, $host, $port = 22)
    {
        $this->setName($name);
        $this->setHost($host);
        $this->setPort($port);
    }

    /**
     * Get authentication method
     *
     * @return int
     */
    public function getAuthenticationMethod()
    {
        return $this->authenticationMethod;
    }

    /**
     * Set authentication method
     *
     * @param int $authenticationMethod
     *
     * @return Configuration
     */
    public function setAuthenticationMethod($authenticationMethod)
    {
        $this->authenticationMethod = $authenticationMethod;

        return $this;
    }

    /**
     * Get configuration file
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Set configuration file
     *
     * @param string $configFile
     *
     * @return Configuration
     */
    public function setConfigFile($configFile)
    {
        $this->configFile = $this->parseHome($configFile);

        return $this;
    }

    /**
     * Get host for connection
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set host for connection
     *
     * @param string $host
     *
     * @return Configuration
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get password for connection
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getRealPassword($this->password);
    }

    /**
     * Set password for connection
     *
     * @param string $password
     *
     * @return Configuration
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set port for connection
     *
     * @param int $port
     *
     * @return Configuration
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get public key
     *
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Set public key
     *
     * @param string $path
     *
     * @return Configuration
     */
    public function setPublicKey($path)
    {
        $this->publicKey = $this->parseHome($path);

        return $this;
    }

    /**
     * Get pass phrase
     *
     * @return string
     */
    public function getPassPhrase()
    {
        return $this->getRealPassword($this->passPhrase);
    }

    /**
     * Set pass phrase
     *
     * @param string $passPhrase
     *
     * @return Configuration
     */
    public function setPassPhrase($passPhrase)
    {
        $this->passPhrase = $passPhrase;

        return $this;
    }

    /**
     * Get private key
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set private key
     *
     * @param string $path
     *
     * @return Configuration
     */
    public function setPrivateKey($path)
    {
        $this->privateKey = $this->parseHome($path);

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param string $user
     *
     * @return Configuration
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Configuration
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get pem file
     *
     * @return string
     */
    public function getPemFile()
    {
        return $this->pemFile;
    }

    /**
     * To auth with pem file use pemFile() method instead of this.
     *
     * @param string $pemFile
     *
     * @return Configuration
     */
    public function setPemFile($pemFile)
    {
        $this->pemFile = $this->parseHome($pemFile);

        return $this;
    }

    /**
     * Parse "~" symbol from path.
     *
     * @param string $path
     *
     * @return string
     */
    private function parseHome($path)
    {
        if (isset($_SERVER['HOME'])) {
            $path = str_replace('~', $_SERVER['HOME'], $path);
        } elseif (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            $path = str_replace('~', $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'], $path);
        }

        return $path;
    }

    /**
     * Get real password
     *
     * @param mixed $password
     *
     * @return string
     */
    private function getRealPassword($password)
    {
        if ($password instanceof PasswordGetterInterface) {
            return $password->getPassword($this->getHost(), $this->getUser());
        }

        return $password;
    }

    /**
     * Set pty
     *
     * @param $pty
     */
    public function setPty($pty)
    {
        $this->pty = $pty;
    }

    /**
     * Get pty option
     *
     * @return mixed
     */
    public function getPty()
    {
        return $this->pty;
    }

    /**
     * Set pty for ssh2 connection. For retro compatibility
     *
     * @param $ssh2Pty
     * @deprecated
     */
    public function setSsh2Pty($ssh2Pty)
    {
        $this->setPty($ssh2Pty);
    }

    /**
     * Get pty option for ssh2 connection. For retro compatibility
     *
     * @deprecated
     * @return mixed
     */
    public function getSsh2Pty()
    {
        return $this->getPty();
    }
}
