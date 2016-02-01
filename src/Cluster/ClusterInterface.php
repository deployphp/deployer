<?php

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * Cluster Interface
 */

namespace Deployer\Cluster;

interface ClusterInterface
{
    /**
     * @return array | \Deployer\Cluster\Node[]
     */
    public function getNodes();
}
