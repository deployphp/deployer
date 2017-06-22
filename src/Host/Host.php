<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Configuration\Configuration;
use Deployer\Configuration\ConfigurationAccessor;
use Deployer\Ssh\Arguments;
use Deployer\Ssh\Options;

class Host
{
    use ConfigurationAccessor;

    private $hostname;
    private $realHostname;
    private $user;
    private $port;
    private $configFile;
    private $identityFile;
    private $forwardAgent = true;
    private $multiplexing = null;
    private $sshArguments;

    /**
     * @param string $hostname
     */
    public function __construct(string $hostname)
    {
        $this->hostname = $hostname;
        $this->setRealHostname($hostname);
        $this->config = new Configuration();
        $this->sshArguments = new Arguments();
    }

    private function initOptions()
    {
        if ($this->port) {
            $this->sshArguments = $this->sshArguments->withFlag('-p', $this->port);
        }

        if ($this->configFile) {
            $this->sshArguments = $this->sshArguments->withFlag('-F', $this->configFile);
        }

        if ($this->identityFile) {
            $this->sshArguments = $this->sshArguments->withFlag('-i', $this->identityFile);
        }

        if ($this->forwardAgent) {
            $this->sshArguments = $this->sshArguments->withFlag('-A');
        }
    }

    /**
     * Returns pair user/hostname
     *
     * @return string
     */
    public function __toString()
    {
        $user = empty($this->user) ? '' : "{$this->user}@";
        return "$user{$this->realHostname}";
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @return mixed
     */
    public function getRealHostname()
    {
        return $this->realHostname;
    }

    /**
     * @param string $hostname
     * @return $this
     */
    public function hostname(string $hostname)
    {
        $this->setRealHostname($hostname);
        return $this;
    }

    /**
     * @param mixed $hostname
     */
    private function setRealHostname(string $hostname)
    {
        $this->realHostname = preg_replace('/\/.+$/', '', $hostname);
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function user(string $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return $this
     */
    public function port(int $port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * @param string $configFile
     * @return $this
     */
    public function configFile(string $configFile)
    {
        $this->configFile = $configFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentityFile()
    {
        return $this->identityFile;
    }

    /**
     * @param string $identityFile
     * @return $this
     */
    public function identityFile(string $identityFile)
    {
        $this->identityFile = $identityFile;
        return $this;
    }

    /**
     * @return bool
     */
    public function isForwardAgent()
    {
        return $this->forwardAgent;
    }

    /**
     * @param bool $forwardAgent
     * @return $this
     */
    public function forwardAgent(bool $forwardAgent = true)
    {
        $this->forwardAgent = $forwardAgent;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiplexing()
    {
        return $this->multiplexing;
    }

    /**
     * @param bool $multiplexing
     * @return $this
     */
    public function multiplexing(bool $multiplexing = true)
    {
        $this->multiplexing = $multiplexing;
        return $this;
    }

    public function getSshArguments()
    {
        $this->initOptions();
        return $this->sshArguments;
    }

    public function sshOptions(array $options) : Host
    {
        $this->sshArguments = $this->sshArguments->withOptions($options);
        return $this;
    }

    public function sshFlags(array $flags) : Host
    {
        $this->sshArguments = $this->sshArguments->withFlags($flags);
        return $this;
    }

    public function addSshOption(string $option, $value) : Host
    {
        $this->sshArguments = $this->sshArguments->withOption($option, $value);
        return $this;
    }

    public function addSshFlag(string $flag, string $value = null) : Host
    {
        $this->sshArguments = $this->sshArguments->withFlag($flag, $value);
        return $this;
    }

    /**
     * Set stage
     *
     * @param string $stage
     * @return $this
     */
    public function stage(string $stage)
    {
        $this->config->set('stage', $stage);
        return $this;
    }

    /**
     * Set roles
     *
     * @param array ...$roles
     * @return $this
     */
    public function roles(...$roles)
    {
        $this->config->set('roles', []);

        foreach ($roles as $role) {
            $this->config->add('roles', [$role]);
        }

        return $this;
    }

    /**
     * Set become
     *
     * @param string $user
     * @return $this
     */
    public function become(string $user)
    {
        $this->config->set('become', $user);
        return $this;
    }
}
