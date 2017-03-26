<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Collection\Collection;

/**
 * @method Task get($name)
 */
class TaskCollection extends Collection
{
    protected function throwNotFound(string $name)
    {
        throw new \InvalidArgumentException("Task `$name` not found");
    }
}
