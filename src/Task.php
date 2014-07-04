<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class Task implements TaskInterface
{
    /**
     * List of all tasks.
     * @var TaskInterface[]
     */
    private static $tasks = [];

    /**
     * Callable body of current task.
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Create task and save to tasks list.
     * @param string $name Task name.
     * @param callable $callback Code of task.
     */
    public static function create($name, \Closure $callback)
    {
        self::$tasks[$name] = new self($callback);
    }

    /**
     * @return TaskInterface[]
     */
    public static function getTasks()
    {
        return self::$tasks;
    }

    /**
     * Run callback of current task.
     */
    public function run()
    {
        call_user_func($this->callback);
    }
}