<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Host\Host;
use Deployer\Task\Task;

interface ExecutorInterface
{
    /**
     * @param Task[] $tasks
     * @param Host[] $hosts
     */
    public function run(array $tasks, array $hosts);
}
