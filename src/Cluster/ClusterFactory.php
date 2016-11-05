<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

use Deployer\Deployer;

/**
 * Cluster Factory
 * Creates and return an instance of \Deployer\Cluster\Cluster
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
class ClusterFactory
{
    /**
     * This class should not be initialized,
     * so set the __construct as private
     */
    private function __construct()
    {
    }

    /**
     * @param \Deployer\Deployer $deployer
     * @param string $name
     * @param array $nodes
     * @param int $port
     *
     * @return \Deployer\Cluster\Cluster
     */
    public static function create(Deployer $deployer, $name, $nodes, $port = 22)
    {
        $cluster = new Cluster($deployer, $name, $nodes, $port);
        return $cluster;
    }
}
