<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Server\Password\AskPasswordGetter;
use Deployer\Server\Password\PasswordGetterInterface;

/**
 * Build server configuration
 */
class Builder
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Environment
     */
    protected $env;

    /**
     * Construct
     *
     * @param Configuration $config
     * @param Environment   $env
     */
    public function __construct(Configuration $config, Environment $env)
    {
        $this->config = $config;
        $this->env = $env;

        $env->setAsProtected('server', [
            'name' => $config->getName(),
            'host' => $config->getHost(),
            'port' => $config->getPort(),
        ]);
    }

    /**
     * Define user name for authentication.
     *
     * @param string $name
     *
     * @return Builder
     */
    public function user($name)
    {
        $this->config->setUser($name);

        return $this;
    }

    /**
     * Set password for connection
     *
     * @param string|PasswordGetterInterface $password If you did not define password it will be asked on connection.
     *
     * @return Builder
     */
    public function password($password = null)
    {
        $password = $this->checkPassword($password);

        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_PASSWORD);
        $this->config->setPassword($password);

        return $this;
    }

    /**
     * Define server host
     *
     * @param string $host
     *
     * @return Builder
     */
    public function host($host)
    {
        $this->config->setHost($host);

        return $this;
    }

    /**
     * Define server port
     *
     * @param int $port
     *
     * @return Builder
     */
    public function port($port)
    {
        $this->config->setPort($port);

        return $this;
    }

    /**
     * If you use an ssh config file you can user it.
     *
     * @param string $file Config file path
     *
     * @return Builder
     */
    public function configFile($file)
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_CONFIG);
        $this->config->setConfigFile($file);

        return $this;
    }

    /**
     * Authenticate with public key
     *
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param string $passPhrase
     *
     * @return Builder
     */
    public function identityFile($publicKeyFile = '~/.ssh/id_rsa.pub', $privateKeyFile = '~/.ssh/id_rsa', $passPhrase = '')
    {
        $passPhrase = $this->checkPassword($passPhrase);

        if (is_null($publicKeyFile)) {
            // Use default value
            $publicKeyFile = '~/.ssh/id_rsa.pub';
        }

        if (is_null($privateKeyFile)) {
            // Use default value
            $privateKeyFile = '~/.ssh/id_rsa';
        }

        if (is_null($passPhrase)) {
            // Ask pass phrase before connection
            $passPhrase = AskPasswordGetter::createLazyGetter();
        }

        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_IDENTITY_FILE);
        $this->config->setPublicKey($publicKeyFile);
        $this->config->setPrivateKey($privateKeyFile);
        $this->config->setPassPhrase($passPhrase);

        return $this;
    }

    /**
     * Authenticate with pem file
     *
     * @param string $pemFile
     *
     * @return Builder
     */
    public function pemFile($pemFile)
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_PEM_FILE);
        $this->config->setPemFile($pemFile);

        return $this;
    }

    /**
     * Using forward agent to authentication
     *
     * @return Builder
     */
    public function forwardAgent()
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_AGENT);

        return $this;
    }

    /**
     * Set env variable
     *
     * @param string           $name
     * @param array|int|string $value
     *
     * @return Builder
     */
    public function env($name, $value)
    {
        $this->env->set($name, $value);

        return $this;
    }

    /**
     * Indicate stage
     *
     * @param string|array $stages  Name or array on server stages.
     *
     * @return Builder
     */
    public function stage($stages)
    {
        $this->env->set('stages', (array) $stages);

        return $this;
    }

    /**
     * Check password valid
     *
     * @param mixed $password
     *
     * @return mixed
     */
    private function checkPassword($password)
    {
        if (is_null($password)) {
            return AskPasswordGetter::createLazyGetter();
        }

        if (is_scalar($password)) {
            return $password;
        }

        if (is_object($password) && $password instanceof PasswordGetterInterface) {
            return $password;
        }

        // Invalid password
        throw new \InvalidArgumentException(sprintf(
            'The password should be a string or PasswordGetterInterface instances, but "%s" given.',
            is_object($password) ? get_class($password) : gettype($password)
        ));
    }
}
