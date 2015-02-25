<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

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
     * @param Configuration $config
     * @param Environment $env
     */
    public function __construct(Configuration $config, Environment $env)
    {
        $this->config = $config;
        $this->env = $env;
    }

    /**
     * Define user name for authentication.
     * @param string $name
     * @param null|string $password If you did not define password it will be asked on connection.
     * @return $this
     */
    public function user($name, $password = null)
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_PASSWORD);
        $this->config->setUser($name);
        $this->config->setPassword($password);
        return $this;
    }

    /**
     * Define server host
     * @param int $host
     * @return $this
     */
    public function host($host)
    {
        $this->config->setHost($host);
        return $this;
    }

    /**
     * Define server port
     * @param int $port
     * @return $this
     */
    public function port($port)
    {
        $this->config->setPort($port);
        return $this;
    }

    /**
     * If you use an ssh config file you can user it.
     * @param string $file Config file path
     * @return $this
     */
    public function configFile($file)
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_CONFIG);
        $this->config->setConfigFile($file);
        return $this;
    }

    /**
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param string $passPhrase
     * @return $this
     */
    public function pubKey($publicKeyFile = '~/.ssh/id_rsa.pub', $privateKeyFile = '~/.ssh/id_rsa', $passPhrase = '')
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_PUBLIC_KEY);
        $this->config->setPublicKey($publicKeyFile);
        $this->config->setPrivateKey($privateKeyFile);
        $this->config->setPassPhrase($passPhrase);
        return $this;
    }

    /**
     * @param $pemFile
     * @return $this
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
     * @return $this
     */
    public function forwardAgent()
    {
        $this->config->setAuthenticationMethod(Configuration::AUTH_BY_AGENT);
        return $this;
    }

    /**
     * @param string $name
     * @param array|int|string $value
     * @return $this
     */
    public function env($name, $value)
    {
        $this->env->set($name, $value);
        return $this;
    }

    /**
     * @param string|array $stages  Name or array on server stages.
     * @return $this
     */
    public function stage($stages)
    {
        $this->env->set('stages', (array)$stages);
        return $this;
    }
}
