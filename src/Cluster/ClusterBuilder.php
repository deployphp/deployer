<?php

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * Cluster Node
 * Defines a node for cluster
 */

namespace Deployer\Cluster;

use Deployer\Cluster\Node;

/**
 * @property array | Deployer\Cluster\Node[] $nodes
 */

class ClusterBuilder
{
    /**
     * @var array | Deployer\Cluster\Node[]
     */
    protected $nodes = null;

    /**
     * @param array | Deployer\Cluster\Node[] $nodes
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }
    
    /**
     * @param string $user
     * @return ClusterBuilder
     */
    public function user($user)
    {
        foreach ($this->nodes as $node) {
            $node->builder->user($user);
        }
        return $this;
    }

    /**
     * @param string $password
     * @return ClusterBuilder
     */
    public function password($password = null)
    {
        foreach ($this->nodes as $node) {
            $node->builder->password($password);
        }
        return $this;
    }

    /**
     * @param string $file
     * @return ClusterBuilder
     */
    public function configFile($file = '~/.ssh/config')
    {
        foreach ($this->nodes as $node) {
            $node->builder->configFile($file);
        }
        return $this;
    }

    /**
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param string $passPhrase
     * @return ClusterBuilder
     */
    public function identityFile(
        $publicKeyFile = '~/.ssh/id_rsa.pub',
        $privateKeyFile = '~/.ssh/id_rsa',
        $passPhrase = ''
    ) {
        foreach ($this->nodes as $node) {
            $node->builder->identityFile($publicKeyFile, $privateKeyFile, $passPhrase);
        }
        return $this;
    }
    
    /**
     * @param string $pemFile
     * @return ClusterBuilder
     */
    public function pemFile($pemFile)
    {
        foreach ($this->nodes as $node) {
            $node->builder->pemFile($pemFile);
        }
        return $this;
    }

    /**
     * @return ClusterBuilder
     */
    public function forwardAgent()
    {
        foreach ($this->nodes as $node) {
            $node->builder->forwardAgent();
        }
        return $this;
    }

    /**
     * @param string $name
     * @param array|string $env
     * @return ClusterBuilder
     */
    public function env($name, $env)
    {
        foreach ($this->nodes as $node) {
            $node->builder->env($name, $env);
        }
        return $this;
    }



    /**
     * @param string | array $stages
     * @return ClusterBuilder
     */
    public function stage($stages)
    {
        foreach ($this->nodes as $node) {
            $node->builder->stage($stages);
        }
        return $this;
    }
}
