<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Task;
use Deployer\TaskInterface;

class TaskFactory
{
    /**
     * List of all tasks.
     * @var TaskInterface[]
     */
    private static $tasks = [];

    /**
     * Create task and save to tasks list.
     * @param string $name Task name.
     * @param callable|array $callback Code of task or array of other tasks.
     * @return TaskInterface
     */
    public static function create($name, $callback)
    {
        if (is_callable($callback)) {
            return self::$tasks[$name] = new Task($callback);
        //} elseif (is_array($callback)) {

        } else {
            throw new \InvalidArgumentException("Task can be an closure or array of other tasks names.");
        }

    }

    /**
     * @return TaskInterface[]
     */
    public static function getTasks()
    {
        return self::$tasks;
    }
} 