<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Ssh;

class Configuration
{
    const AUTH_BY_PASSWORD = 0;

    const AUTH_BY_CONFIG = 1;

    const AUTH_BY_PUBLIC_KEY = 2;

    const AUTH_BY_PEM_FILE = 3;

    /**
     * Type of authentication.
     * @var int
     */
    private $authenticationMethod = self::AUTH_BY_PASSWORD;

    /**
     * Server name
     * @var string
     */
    private $name;

    /**
     * Server host.
     * @var string
     */
    private $host;

    /**
     * Server port.
     * @var int
     */
    private $port;

    /**
     * Base path of server.
     * @var string
     */
    private $path;

    /**
     * User of remote server.
     * @var string
     */
    private $user;

    /**
     * Used for authentication with password.
     * @var string
     */
    private $password;

    /**
     * Used for authentication with config file.
     * @var string
     */
    private $configFile;

    /**
     * Used for authentication with public key.
     * @var string
     */
    private $publicKey;

    /**
     * Used for authentication with public key.
     * @var string
     */
    private $privateKey;

    /**
     * Used for authentication with public key.
     * @var string
     */
    private $passPhrase;

    /**
     * WWW user name.
     * @var string
     */
    private $wwwUser = 'www-data';

    /**
     * Pem file.
     * @var string
     */
    private $pemFile;

    /**
     * PHP executable path
     * @var type
     */
    private $phpPath = 'php';

    /**
     * @param string $host
     * @param int $port
     */
    public function __construct($name, $host, $port = 22)
    {
        $this->setName($name);
        $this->setHost($host);
        $this->setPort($port);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function path($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Define user name for authentication.
     * @param string $name
     * @param null|string $password If you did not define password it will be asked on connection.
     * @return $this
     */
    public function user($name, $password = null)
    {
        $this->setAuthenticationMethod(self::AUTH_BY_PASSWORD);
        $this->setUser($name);
        $this->setPassword($password);
        return $this;
    }

    /**
     * If you use an ssh config file you can user it.
     * @param string $file Config file path
     * @return $this
     */
    public function configFile($file)
    {
        $this->setAuthenticationMethod(self::AUTH_BY_CONFIG);
        $this->setConfigFile($file);
        return $this;
    }

    /**
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param null|string $passPhrase
     * @return $this
     */
    public function pubKey($publicKeyFile = '~/.ssh/id_rsa.pub', $privateKeyFile = '~/.ssh/id_rsa', $passPhrase = '')
    {
        $this->setAuthenticationMethod(self::AUTH_BY_PUBLIC_KEY);
        $this->setPublicKey($publicKeyFile);
        $this->setPrivateKey($privateKeyFile);
        $this->setPassPhrase($passPhrase);
        return $this;
    }

    public function pemFile($pemFile)
    {
        $this->setAuthenticationMethod(self::AUTH_BY_PEM_FILE);
        $this->setPemFile($pemFile);
        return $this;
    }

    /**
     * @return int
     */
    public function getAuthenticationMethod()
    {
        return $this->authenticationMethod;
    }

    /**
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        if(null === $this->password) {
            $this->password = askHiddenResponse('Password:');
        }

        return $this->password;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return array
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPassPhrase()
    {
        if(null === $this->passPhrase) {
            $this->passPhrase = askHiddenResponse('PassPhrase:');
        }
        return $this->passPhrase;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        if(null === $this->user) {
            $this->user = ask("User:", trim(runLocally('whoami')));
        }
        return $this->user;
    }

    /**
     * @param int $authenticationMethod
     */
    public function setAuthenticationMethod($authenticationMethod)
    {
        $this->authenticationMethod = $authenticationMethod;
    }

    /**
     * @param string $configFile
     */
    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $passPhrase
     * @return $this
     */
    public function setPassPhrase($passPhrase)
    {
        $this->passPhrase = $passPhrase;
        return $this;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPrivateKey($path)
    {
        if (isset($_SERVER['HOME'])) {
            $path = str_replace('~', $_SERVER['HOME'], $path);
        }

        $this->privateKey = $path;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPublicKey($path)
    {
        if (isset($_SERVER['HOME'])) {
            $path = str_replace('~', $_SERVER['HOME'], $path);
        }

        $this->publicKey = $path;
        return $this;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $wwwUser
     * @return $this
     */
    public function setWwwUser($wwwUser)
    {
        $this->wwwUser = $wwwUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getWwwUser()
    {
        return $this->wwwUser;
    }

    /**
     * To auth with pem file use pemFile() method instead of this.
     * @param string $pemFile
     * @return $this
     */
    private function setPemFile($pemFile)
    {
        if (isset($_SERVER['HOME'])) {
            $pemFile = str_replace('~', $_SERVER['HOME'], $pemFile);
        }
        $this->pemFile = $pemFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getPemFile()
    {
        return $this->pemFile;
    }

    /**
     * Set path to php executable
     * @param string $phpPath
     */
    public function setPhpPath($phpPath)
    {
        $this->phpPath = $phpPath;
    }

    /**
     * @return string
     */
    public function getPhpPath()
    {
        return $this->phpPath;
    }
}
