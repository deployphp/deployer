<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Configuration\Configuration;
use Deployer\Component\Ssh\Arguments;
use Deployer\Deployer;

class Host
{
    private $config;
    private $sshArguments;

    public function __construct(string $hostname)
    {
        $parent = null;
        if (Deployer::get()) {
            $parent = Deployer::get()->config;
        }
        $this->config = new Configuration($parent);
        $this->set('alias', $hostname);
        $this->set('hostname', preg_replace('/\/.+$/', '', $hostname));
        $this->set('remote_user', '');
        $this->set('port', '');
        $this->set('config_file', '');
        $this->set('identity_file', '');
        $this->set('forward_agent', true);
        $this->set('shell', 'bash -s');
        $this->sshArguments = new Arguments();
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function set(string $name, $value)
    {
        $this->config->set($name, $value);
        return $this;
    }

    public function add(string $name, array $value)
    {
        $this->config->add($name, $value);
        return $this;
    }

    public function has(string $name): bool
    {
        return $this->config->has($name);
    }

    public function get(string $name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    public function getAlias()
    {
        return $this->config->get('alias');
    }

    public function setTag(string $tag)
    {
        $this->config->set('tag', $tag);
        return $this;
    }

    public function getTag(): string
    {
        return $this->config->get('tag', $this->generateTag());
    }

    public function setHostname(string $hostname)
    {
        $this->config->set('hostname', $hostname);
        return $this;
    }

    public function getHostname()
    {
        return $this->config->get('hostname');
    }

    public function setRemoteUser($user)
    {
        $this->config->set('remote_user', $user);
        return $this;
    }

    public function getRemoteUser()
    {
        return $this->config->get('remote_user');
    }

    public function setPort(int $port)
    {
        $this->config->set('port', $port);
        return $this;
    }

    public function getPort()
    {
        return $this->config->get('port');
    }

    public function setConfigFile(string $file)
    {
        $this->config->set('config_file', $file);
        return $this;
    }

    public function getConfigFile()
    {
        return $this->config->get('config_file');
    }

    public function setIdentityFile($file)
    {
        $this->config->set('identity_file', $file);
        return $this;
    }

    public function getIdentityFile()
    {
        return $this->config->get('identity_file');
    }

    public function setForwardAgent(bool $on)
    {
        $this->config->set('forward_agent', $on);
        return $this;
    }

    public function getForwardAgent()
    {
        return $this->config->get('forward_agent');
    }

    public function setSshMultiplexing(bool $on)
    {
        $this->config->set('ssh_multiplexing', $on);
        return $this;
    }

    public function getSshMultiplexing()
    {
        return $this->config->get('ssh_multiplexing');
    }

    public function setShell(string $command)
    {
        $this->config->set('shell', $command);
        return $this;
    }

    public function getShell(): string
    {
        return $this->config->get('shell');
    }

    public function getConnectionString(): string
    {
        if ($this->get('remote_user') !== '') {
            return $this->get('remote_user') . '@' . $this->get('hostname');
        }
        return $this->get('hostname');
    }

    public function getSshArguments()
    {
        $this->initOptions();
        return $this->sshArguments;
    }

    // TODO: Migrate to configuration.

    public function setSshOptions(array $options)
    {
        $this->sshArguments = $this->sshArguments->withOptions($options);
        return $this;
    }

    // TODO: Migrate to configuration.

    public function setSshFlags(array $flags)
    {
        $this->sshArguments = $this->sshArguments->withFlags($flags);
        return $this;
    }

    private function initOptions()
    {
        if ($this->getPort()) {
            $this->sshArguments = $this->sshArguments->withFlag('-p', $this->getPort());
        }

        if ($this->getConfigFile()) {
            $this->sshArguments = $this->sshArguments->withFlag('-F', $this->getConfigFile());
        }

        if ($this->getIdentityFile()) {
            $this->sshArguments = $this->sshArguments->withFlag('-i', $this->getIdentityFile());
        }

        if ($this->getForwardAgent()) {
            $this->sshArguments = $this->sshArguments->withFlag('-A');
        }
    }

    private function generateTag()
    {
        if (defined('NO_ANSI')) {
            return $this->getAlias();
        }

        if ($this->getAlias() === 'localhost') {
            return $this->getAlias();
        }

        if (getenv('COLORTERM') === 'truecolor') {
            $hsv = function ($h, $s, $v) {
                $r = $g = $b = $i = $f = $p = $q = $t = 0;
                $i = floor($h * 6);
                $f = $h * 6 - $i;
                $p = $v * (1 - $s);
                $q = $v * (1 - $f * $s);
                $t = $v * (1 - (1 - $f) * $s);
                switch ($i % 6) {
                    case 0:
                        $r = $v;
                        $g = $t;
                        $b = $p;
                        break;
                    case 1:
                        $r = $q;
                        $g = $v;
                        $b = $p;
                        break;
                    case 2:
                        $r = $p;
                        $g = $v;
                        $b = $t;
                        break;
                    case 3:
                        $r = $p;
                        $g = $q;
                        $b = $v;
                        break;
                    case 4:
                        $r = $t;
                        $g = $p;
                        $b = $v;
                        break;
                    case 5:
                        $r = $v;
                        $g = $p;
                        $b = $q;
                        break;
                }
                $r = round($r * 255);
                $g = round($g * 255);
                $b = round($b * 255);
                return "\x1b[38;2;{$r};{$g};{$b}m";
            };

            $total = 100;
            $colors = [];
            for ($i = 0; $i < $total; $i++) {
                $colors[] = $hsv($i / $total, 1, .9);
            }

            $alias = $this->getAlias();
            $tag = $colors[abs(crc32($alias)) % count($colors)];

            return "{$tag}{$alias}\x1b[0m";
        }


        $colors = [
            'fg=cyan;options=bold',
            'fg=green;options=bold',
            'fg=yellow;options=bold',
            'fg=cyan',
            'fg=blue',
            'fg=yellow',
            'fg=magenta',
            'fg=blue;options=bold',
            'fg=green',
            'fg=magenta;options=bold',
            'fg=red;options=bold',
        ];
        $alias = $this->getAlias();
        $tag = $colors[abs(crc32($alias)) % count($colors)];

        return "<{$tag}>{$alias}</>";
    }
}
