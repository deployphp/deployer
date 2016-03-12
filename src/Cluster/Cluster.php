<?php
/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * Cluster
 */
namespace Deployer\Cluster;

use Deployer\Deployer;
use Deployer\Cluster\ClusterInterface;
use Deployer\Cluster\ClusterBuilder;

/**
 * @property string $name
 * @property array $nodes
 * @property int $port
 * @property ClusterBuilder $clusterBuilder
 *
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
     * @var string|integer $port
     */
    protected $port = null;

    /**
     * @var ClusterBuilder $clusterBuilder
     */
    protected $clusterBuilder = null;

    /**
     * @param Deployer $deployer
     * @param string $name
     * @param array $nodes
     * @param int $port
     *
     * @throws InvalidArgumentException
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
     * @return \Deployer\Cluster\ClusterBuilder
     */
    public function getBuilder()
    {
        return $this->clusterBuilder;
    }
    
    /**
     * @return array | \Deployer\Cluster\Node[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }
}
