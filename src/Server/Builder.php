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
    public function forwardComposerAuth()
    {
        $this->config->setComposerAuth($this->getComposerAuth());

        return $this;
    }


    protected static function getComposerAuth()
    {
        $composerAuth = '';

        $environment = getenv('COMPOSER_AUTH');
        if ($environment) {
            $composerAuth = json_decode($environment);
        }

        if (empty($composerAuth)){
            $filename = self::getComposerHomeDir() . DIRECTORY_SEPARATOR . 'auth.json';
            if (file_exists(realpath($filename))) {
                $composerAuth = json_decode(file_get_contents($filename));
            }
        }

        if (!empty($composerAuth)) {
            return json_encode($composerAuth);
        }
        return '';
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    protected static function getComposerHomeDir()
    {
        $home = getenv('COMPOSER_HOME');
        if ($home) {
            return $home;
        }

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            if (!getenv('APPDATA')) {
                throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
            }

            return rtrim(strtr(getenv('APPDATA'), '\\', '/'), '/') . '/Composer';
        }

        $userDir = self::getUserDir();
        if (is_dir($userDir . '/.composer')) {
            return $userDir . '/.composer';
        }

        if (self::useXdg()) {
            // XDG Base Directory Specifications
            $xdgConfig = getenv('XDG_CONFIG_HOME') ?: $userDir . '/.config';

            return $xdgConfig . '/composer';
        }

        return $userDir . '/.composer';
    }

    /**
     * @return bool
     */
    private static function useXdg()
    {
        foreach (array_keys($_SERVER) as $key) {
            if (substr($key, 0, 4) === 'XDG_') {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    private static function getUserDir()
    {
        $home = getenv('HOME');
        if (!$home) {
            throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
        }

        return rtrim(strtr($home, '\\', '/'), '/');
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
     * Use pty in ssh2 connection
     *
     * @param $ssh2Pty
     * @return BuilderInterface
     */
    public function ssh2Pty($ssh2Pty)
    {
        $this->config->setSsh2Pty($ssh2Pty);

        return $this;
    }
}
