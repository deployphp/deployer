<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

class Host
{
    private $hostname;
    private $user;
    private $port;
    private $configFile;
    private $identityFile;
    private $forwardAgent = true;
    private $multiplexing = true;
    private $options = [];

    /**
     * Host constructor.
     * @param string $hostname
     */
    public function __construct(string $hostname)
    {
        $this->hostname = $hostname;
    }

    public function generateOptionsString()
    {
        return '';
    }

    /**
     * Returns pair user/hostname
     *
     * @return string
     */
    public function __toString()
    {
        $user = empty($this->user) ? '' : "{$this->user}@";
        $hostname = $this->hostname;
        return "$user$hostname";
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;
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
     */
    public function setUser(string $user)
    {
        $this->user = $user;
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
     */
    public function setPort(int $port)
    {
        $this->port = $port;
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
     */
    public function setConfigFile(string $configFile)
    {
        $this->configFile = $configFile;
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
     */
    public function setIdentityFile(string $identityFile)
    {
        $this->identityFile = $identityFile;
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
     */
    public function setForwardAgent(bool $forwardAgent)
    {
        $this->forwardAgent = $forwardAgent;
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
     */
    public function setMultiplexing(bool $multiplexing)
    {
        $this->multiplexing = $multiplexing;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $option
     */
    public function addOption(string $option)
    {
        $this->options[] = $option;
    }
}
