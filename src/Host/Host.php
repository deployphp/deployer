<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Configuration\Configuration;
use Deployer\Deployer;
use Deployer\Exception\ConfigurationException;
use Deployer\Exception\Exception;
use Deployer\Task\Context;
use function Deployer\Support\colorize_host;
use function Deployer\Support\parse_home_dir;

class Host
{
    /**
     * @var Configuration $config
     */
    private $config;

    public function __construct(string $hostname)
    {
        $parent = null;
        if (Deployer::get()) {
            $parent = Deployer::get()->config;
        }
        $this->config = new Configuration($parent);
        $this->set('#alias', $hostname);
        $this->set('hostname', preg_replace('/\/.+$/', '', $hostname));
    }

    public function __toString(): string
    {
        return $this->getTag();
    }

    public function config(): Configuration
    {
        return $this->config;
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): self
    {
        if ($name === 'alias') {
            throw new ConfigurationException("Can not update alias of the host.\nThis will change only host own alias,\nbut not the key it is stored in HostCollection.");
        }
        if ($name === '#alias') {
            $name = 'alias';
        }
        $this->config->set($name, $value);
        return $this;
    }

    public function add(string $name, array $value): self
    {
        $this->config->add($name, $value);
        return $this;
    }

    public function has(string $name): bool
    {
        return $this->config->has($name);
    }

    public function hasOwn(string $name): bool
    {
        return $this->config->hasOwn($name);
    }

    /**
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    public function getAlias(): ?string
    {
        return $this->config->get('alias', null);
    }

    public function setTag(string $tag): self
    {
        $this->config->set('tag', $tag);
        return $this;
    }

    public function getTag(): ?string
    {
        return $this->config->get('tag', colorize_host($this->getAlias()));
    }

    public function setHostname(string $hostname): self
    {
        $this->config->set('hostname', $hostname);
        return $this;
    }

    public function getHostname(): ?string
    {
        return $this->config->get('hostname', null);
    }

    public function setRemoteUser(string $user): self
    {
        $this->config->set('remote_user', $user);
        return $this;
    }

    public function getRemoteUser(): ?string
    {
        return $this->config->get('remote_user', null);
    }

    /**
     * @param string|int|null $port
     * @return $this
     */
    public function setPort($port): self
    {
        $this->config->set('port', $port);
        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getPort()
    {
        return $this->config->get('port', null);
    }

    public function setConfigFile(string $file): self
    {
        $this->config->set('config_file', $file);
        return $this;
    }

    public function getConfigFile(): ?string
    {
        return $this->config->get('config_file', null);
    }

    public function setIdentityFile(string $file): self
    {
        $this->config->set('identity_file', $file);
        return $this;
    }

    public function getIdentityFile(): ?string
    {
        return $this->config->get('identity_file', null);
    }

    public function setForwardAgent(bool $on): self
    {
        $this->config->set('forward_agent', $on);
        return $this;
    }

    public function getForwardAgent(): ?bool
    {
        return $this->config->get('forward_agent', null);
    }

    public function setSshMultiplexing(bool $on): self
    {
        $this->config->set('ssh_multiplexing', $on);
        return $this;
    }

    public function getSshMultiplexing(): ?bool
    {
        return $this->config->get('ssh_multiplexing', null);
    }

    public function setShell(string $command): self
    {
        $this->config->set('shell', $command);
        return $this;
    }

    public function getShell(): ?string
    {
        return $this->config->get('shell', null);
    }

    public function setDeployPath(string $path): self
    {
        $this->config->set('deploy_path', $path);
        return $this;
    }

    public function getDeployPath(): ?string
    {
        return $this->config->get('deploy_path', null);
    }

    public function setLabels(array $labels): self
    {
        $this->config->set('labels', $labels);
        return $this;
    }

    public function getLabels(): ?array
    {
        return $this->config->get('labels', null);
    }

    public function setSshArguments(array $args): self
    {
        $this->config->set('ssh_arguments', $args);
        return $this;
    }

    public function getSshArguments(): ?array
    {
        return $this->config->get('ssh_arguments', null);
    }

    public function setSshControlPath(string $path): self
    {
        $this->config->set('ssh_control_path', $path);
        return $this;
    }

    public function getSshControlPath(): string
    {
        return $this->config->get('ssh_control_path', $this->generateControlPath());
    }

    private function generateControlPath(): string
    {
        $C = $this->getHostname();
        if ($this->has('remote_user')) {
            $C = $this->getRemoteUser() . '@' . $C;
        }
        if ($this->has('port')) {
            $C .= ':' . $this->getPort();
        }

        // In case of CI environment, lets use shared memory.
        if (getenv('CI') && is_writable('/dev/shm')) {
            return "/dev/shm/$C";
        }

        return "~/.ssh/$C";
    }

    public function connectionString(): string
    {
        if ($this->get('remote_user', '') !== '') {
            return $this->get('remote_user') . '@' . $this->get('hostname');
        }
        return $this->get('hostname');
    }

    public function connectionOptionsString(): string
    {
        return implode(' ', array_map('escapeshellarg', $this->connectionOptionsArray()));
    }

    /**
     * @return string[]
     */
    public function connectionOptionsArray(): array
    {
        $options = [];
        if ($this->has('ssh_arguments')) {
            foreach ($this->getSshArguments() as $arg) {
                $options = array_merge($options, explode(' ', $arg));
            }
        }
        if ($this->has('port')) {
            $options = array_merge($options, ['-p', $this->getPort()]);
        }
        if ($this->has('config_file')) {
            $options = array_merge($options, ['-F', parse_home_dir($this->getConfigFile())]);
        }
        if ($this->has('identity_file')) {
            $options = array_merge($options, ['-i', parse_home_dir($this->getIdentityFile())]);
        }
        if ($this->has('forward_agent') && $this->getForwardAgent()) {
            $options = array_merge($options, ['-A']);
        }
        if ($this->has('ssh_multiplexing') && $this->getSshMultiplexing()) {
            $options = array_merge($options, [
                '-o', 'ControlMaster=auto',
                '-o', 'ControlPersist=60',
                '-o', 'ControlPath=' . $this->getSshControlPath(),
            ]);
        }
        return $options;
    }
}
