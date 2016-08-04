<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

/**
 * Cluster Interface
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
interface ClusterInterface
{
    /**
     * @return array|\Deployer\Cluster\Node[]
     */
    public function getNodes();
}
