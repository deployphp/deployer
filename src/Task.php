<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\AbstractTask;

class Task extends AbstractTask
{
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
     * {@inheritdoc}
     */
    public function run()
    {
        $this->runBeforeTasks();
        call_user_func($this->callback);
        $this->runAfterTasks();
    }
}