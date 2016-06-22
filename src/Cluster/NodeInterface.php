<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

/**
 * NodeInterface
 *
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 */
interface NodeInterface
{
    /**
     * @return \Deployer\Cluster\NodeInterface
     */
    public function initialize();
}
