<?php

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * Cluster Node
 */
namespace Deployer\Cluster;

use Deployer\Deployer;
use Deployer\Server\Environment;
use Deployer\Server\Configuration;
use Deployer\Server\Builder;
use Deployer\Server\Remote\PhpSecLib;
use Deployer\Server\Remote\SshExtension;
use Deployer\Cluster\NodeInterface;

/**
 * @property Deployer $deployer
 * @property Builder $builder
 * @property string $name
 * @property string $host
 */
class Node implements NodeInterface
{
     
    /**
     * @var Deployer $deployer
     */
    protected $deployer = null;

    /**
     * @var Builder $builder
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
     * @var \Deployer\Server\Remote\PhpSecLib | \Deployer\Server\Remote\SshExtension
     */
    protected $server = null;
    
    /**
     * initialize the node
     * @return Node
     */
    public function initialize()
    {
        $env    = new Environment();
        $config = new Configuration($this->name, $this->host, $this->port);
        
        $this->server = new PhpSecLib($config);
        
        if ($this->deployer->parameters->has('ssh_type') &&
            $this->deployer->parameters->get('ssh_type') === 'ext-ssh2'
        ) {
            $this->server = new SshExtension($config);
        }
        $this->builder= new Builder($config, $env);
        
        $this->deployer->servers->set($this->name, $this->server);
        $this->deployer->environments->set($this->name, $env);

        return $this;
    }

    /**
     * @param Deployer $deployer
     */
    public function setDeployer(Deployer $deployer)
    {
        $this->deployer = $deployer;
        return $this;
    }

    /**
     * @param string $name
     * @return Node
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $host
     * @return Node
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }
    
    /**
     * @param int $port
     * @return Node
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @return ServerInterface
     */
    public function getServer()
    {
        return $this->server;
    }
}
