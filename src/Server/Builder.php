<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Builder\BuilderInterface;
use Deployer\Server\Password\AskPasswordGetter;
use Deployer\Server\Password\PasswordGetterInterface;

/**
 * Build server configuration
 */
class Builder implements BuilderInterface
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
     * {@inheritdoc}
     */
    public function user($name)
    {
        $this->config->setUser($name);

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * @return BuilderInterface
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
     * @return BuilderInterface
     */
    public function port($port)
    {
        $this->config->setPort($port);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configFile($file = '~/.ssh/config')
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_CONFIG);
        $this->config->setConfigFile($file);

        return $this;
    }

    /**
     * {@inheritdoc}
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
     * Authenticate with public key + password (2-factor)
     *
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param string $passPhrase
     * @param string $password
     *
     * @return BuilderInterface
     */
    public function identityFileAndPassword($publicKeyFile = '~/.ssh/id_rsa.pub', $privateKeyFile = '~/.ssh/id_rsa', $passPhrase = '', $password = null)
    {
        $this->identityFile($publicKeyFile, $privateKeyFile, $passPhrase);
        $this->password($password);
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_IDENTITY_FILE_AND_PASSWORD);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function pemFile($pemFile)
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_PEM_FILE);
        $this->config->setPemFile($pemFile);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAgent()
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_AGENT);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->env->set($name, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
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

    /**
     * Use pty connection
     *
     * @param $pty
     * @return BuilderInterface
     */
    public function pty($pty)
    {
        $this->config->setPty($pty);

        return $this;
    }

    /**
     * Use pty in ssh2 connection
     *
     * @param $ssh2Pty
     * @deprecated
     * @return BuilderInterface
     */
    public function ssh2Pty($ssh2Pty)
    {
        $this->config->setSsh2Pty($ssh2Pty);

        return $this;
    }
}
