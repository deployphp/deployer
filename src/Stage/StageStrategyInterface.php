<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Stage;

interface StageStrategyInterface
{
    /**
     * @param string $stage
     * @return \Deployer\Server\ServerInterface[]
     */
    public function getServers($stage);
}
