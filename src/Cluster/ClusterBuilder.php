<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

use Deployer\Builder\BuilderInterface;

/**
 * ClusterBuilder
 * Defines a node for cluster
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
class ClusterBuilder implements BuilderInterface
{
    /**
     * @var array|\Deployer\Cluster\Node[]
     */
    protected $nodes = null;

    /**
     * @param array|\Deployer\Cluster\Node[] $nodes
     */
    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function user($user)
    {
        foreach ($this->nodes as $node) {
            $node->builder->user($user);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function password($password = null)
    {
        foreach ($this->nodes as $node) {
            $node->builder->password($password);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function configFile($file = '~/.ssh/config')
    {
        foreach ($this->nodes as $node) {
            $node->builder->configFile($file);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function pemFile($pemFile)
    {
        foreach ($this->nodes as $node) {
            $node->builder->pemFile($pemFile);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAgent()
    {
        foreach ($this->nodes as $node) {
            $node->builder->forwardAgent();
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $env)
    {
        foreach ($this->nodes as $node) {
            $node->builder->set($name, $env);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function stage($stages)
    {
        foreach ($this->nodes as $node) {
            $node->builder->stage($stages);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function pty($stages)
    {
        foreach ($this->nodes as $node) {
            $node->builder->pty($stages);
        }
        return $this;
    }
}
