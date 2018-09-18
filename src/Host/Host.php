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
use function Deployer\Support\array_flatten;

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
    private $shellCommand = 'bash -s';

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
            $this->sshArguments = $this->sshArguments->withFlag('-i', $this->getIdentityFile());
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
        return $this->config->parse($this->hostname);
    }

    /**
     * @return mixed
     */
    public function getRealHostname()
    {
        return $this->config->parse($this->realHostname);
    }

    public function hostname(string $hostname): self
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
        return $this->config->parse($this->user);
    }

    public function user(string $user): self
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

    public function port(int $port): self
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

    public function configFile(string $configFile): self
    {
        $this->configFile = $configFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentityFile()
    {
        return $this->config->parse($this->identityFile);
    }

    public function identityFile(string $identityFile): self
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

    public function forwardAgent(bool $forwardAgent = true): self
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

    public function multiplexing(bool $multiplexing = true): self
    {
        $this->multiplexing = $multiplexing;
        return $this;
    }

    public function getSshArguments()
    {
        $this->initOptions();
        return $this->sshArguments;
    }

    public function sshOptions(array $options): self
    {
        $this->sshArguments = $this->sshArguments->withOptions($options);
        return $this;
    }

    public function sshFlags(array $flags): self
    {
        $this->sshArguments = $this->sshArguments->withFlags($flags);
        return $this;
    }

    public function addSshOption(string $option, $value): self
    {
        $this->sshArguments = $this->sshArguments->withOption($option, $value);
        return $this;
    }

    public function addSshFlag(string $flag, string $value = null): self
    {
        $this->sshArguments = $this->sshArguments->withFlag($flag, $value);
        return $this;
    }

    public function getShellCommand() : string
    {
        return $this->shellCommand;
    }

    public function shellCommand(string $shellCommand): self
    {
        $this->shellCommand = $shellCommand;
        return $this;
    }

    public function stage(string $stage): self
    {
        $this->config->set('stage', $stage);
        return $this;
    }

    public function roles(...$roles): self
    {
        $this->config->set('roles', []);

        foreach (array_flatten($roles) as $role) {
            $this->config->add('roles', [$role]);
        }

        return $this;
    }

    public function become(string $user): self
    {
        $this->config->set('become', $user);
        return $this;
    }
}
