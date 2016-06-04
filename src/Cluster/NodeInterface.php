<?php

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * NodeInterface
 */
namespace Deployer\Cluster;

interface NodeInterface
{
    /**
     * @return \Deployer\Cluster\NodeInterface
     */
    public function initialize();
}
