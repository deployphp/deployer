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
use Deployer\Task\Context;
use function Deployer\Support\colorize_host;

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
        return $this->config->get('alias');
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
        return $this->config->get('hostname');
    }

    public function setRemoteUser(string $user): self
    {
        $this->config->set('remote_user', $user);
        return $this;
    }

    public function getRemoteUser(): ?string
    {
        return $this->config->get('remote_user');
    }

    public function setPort(int $port): self
    {
        $this->config->set('port', $port);
        return $this;
    }

    public function getPort(): ?int
    {
        return $this->config->get('port');
    }

    public function setConfigFile(string $file): self
    {
        $this->config->set('config_file', $file);
        return $this;
    }

    public function getConfigFile(): ?string
    {
        return $this->config->get('config_file');
    }

    public function setIdentityFile(string $file): self
    {
        $this->config->set('identity_file', $file);
        return $this;
    }

    public function getIdentityFile(): ?string
    {
        return $this->config->get('identity_file');
    }

    public function setForwardAgent(bool $on): self
    {
        $this->config->set('forward_agent', $on);
        return $this;
    }

    public function getForwardAgent(): ?bool
    {
        return $this->config->get('forward_agent');
    }

    public function setSshMultiplexing(bool $on): self
    {
        $this->config->set('ssh_multiplexing', $on);
        return $this;
    }

    public function getSshMultiplexing(): ?bool
    {
        return $this->config->get('ssh_multiplexing');
    }

    public function setShell(string $command): self
    {
        $this->config->set('shell', $command);
        return $this;
    }

    public function getShell(): ?string
    {
        return $this->config->get('shell');
    }

    public function setDeployPath(string $path): self
    {
        $this->config->set('deploy_path', $path);
        return $this;
    }

    public function getDeployPath(): ?string
    {
        return $this->config->get('deploy_path');
    }

    public function setLabels(array $labels): self
    {
        $this->config->set('labels', $labels);
        return $this;
    }

    public function getLabels(): ?array
    {
        return $this->config->get('labels');
    }

    public function getConnectionString(): string
    {
        if ($this->get('remote_user', '') !== '') {
            return $this->get('remote_user') . '@' . $this->get('hostname');
        }
        return $this->get('hostname');
    }

    public function setSshArguments(array $args): self
    {
        $this->config->set('ssh_arguments', $args);
        return $this;
    }

    public function getSshArguments(): ?array
    {
        return $this->config->get('ssh_arguments');
    }
}
