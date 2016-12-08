<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

use Deployer\Deployer;
use Deployer\Server\Environment;
use Deployer\Server\Configuration;
use Deployer\Server\Builder;
use Deployer\Server\Remote\PhpSecLib;
use Deployer\Server\Remote\SshExtension;

/**
 * Cluster Node
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
class Node implements NodeInterface
{
    /**
     * @var \Deployer\Deployer
     */
    protected $deployer = null;

    /**
     * @var \Deployer\Builder\BuilderInterface
     */
    public $builder = null;

    /**
     * @var string $name
     */
    protected $name = null;

    /**
     * @var string $host
     */
    protected $host = null;

    /**
     * @var int $port
     */
    protected $port = null;

    /**
     * @var \Deployer\Server\ServerInterface
     */
    protected $server = null;

    /**
     * initialize the node
     * @return \Deployer\Cluster\NodeInterface
     */
    public function initialize()
    {
        $env    = new Environment();
        $config = new Configuration($this->name, $this->host, $this->port);

        $this->server = new PhpSecLib($config);

        if (\Deployer\has('ssh_type') && \Deployer\get('ssh_type') === 'ext-ssh2') {
            $this->server = new SshExtension($config);
        }
        $this->builder = new Builder($config, $env);

        $this->deployer->servers->set($this->name, $this->server);
        $this->deployer->environments->set($this->name, $env);

        return $this;
    }

    /**
     * @param Deployer $deployer
     * @return \Deployer\Cluster\NodeInterface
     */
    public function setDeployer(Deployer $deployer)
    {
        $this->deployer = $deployer;
        return $this;
    }

    /**
     * @param string $name
     * @return \Deployer\Cluster\NodeInterface
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $host
     * @return \Deployer\Cluster\NodeInterface
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return \Deployer\Cluster\NodeInterface
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return \Deployer\Builder\BuilderInterface
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return \Deployer\Server\ServerInterface
     */
    public function getServer()
    {
        return $this->server;
    }
}
