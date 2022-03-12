<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Collection\Collection;

/**
 * @method Task get($name)
 * @method Task[] getIterator()
 */
class TaskCollection extends Collection
{
    protected function throwNotFound(string $name): void
    {
        throw new \InvalidArgumentException("Task `$name` not found.");
    }

    public function add(Task $task): void
    {
        $this->set($task->getName(), $task);
    }
}
