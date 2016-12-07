<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

use Deployer\Builder\BuilderInterface;
use Deployer\Deployer;

/**
 * Cluster
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
class Cluster implements ClusterInterface
{

    /**
     * @var string $name
     */
    protected $name = null;

    /**
     * @var array $nodes
     */
    protected $nodes = null;

    /**
     * @var string|int $port
     */
    protected $port = null;

    /**
     * @var BuilderInterface $clusterBuilder
     */
    protected $clusterBuilder = null;

    /**
     * @param Deployer $deployer
     * @param string $name
     * @param array $nodes
     * @param int $port
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Deployer $deployer, $name, $nodes, $port)
    {
        if (count($nodes) < 1) {
            throw new \InvalidArgumentException('You must define at least one node to deploy');
        }

        $this->name  = $name;
        $this->port  = $port;

        foreach ($nodes as $key => $host) {
            $nName = $name . '_' . $key;
            $node = new Node();

            $node->setDeployer($deployer)
                ->setName($nName)
                ->setHost($host)
                ->setPort($port);

            $node->initialize();
            $this->nodes[]  = $node;
        }
        $this->clusterBuilder = new ClusterBuilder($this->nodes);
    }

    /**
     * @return BuilderInterface
     */
    public function getBuilder()
    {
        return $this->clusterBuilder;
    }

    /**
     * @return array|\Deployer\Cluster\Node[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
