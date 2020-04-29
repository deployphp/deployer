<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use function Deployer\Support\array_flatten;

class ScriptManager
{
    private $tasks;
    private $hooksEnabled = true;

    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run.
     *
     * @return Task[]
     */
    public function getTasks(string $name)
    {
        $tasks = [];

        $task = $this->tasks->get($name);

        if ($this->hooksEnabled) {
            $tasks = array_merge(array_map([$this, 'getTasks'], $task->getBefore()), $tasks);
        }

        if ($task instanceof GroupTask) {
            foreach ($task->getGroup() as $taskName) {
                $subTasks = $this->getTasks($taskName);
                foreach ($subTasks as $subTask) {
                    $subTask->addSelector($task->getSelector());
                    $tasks[] = $subTask;
                }
            }
        } else {
            $tasks[] = $task;
        }

        if ($this->hooksEnabled) {
            $tasks = array_merge($tasks, array_map([$this, 'getTasks'], $task->getAfter()));
        }

        return array_flatten($tasks);
    }

    public function getHooksEnabled()
    {
        return $this->hooksEnabled;
    }

    public function setHooksEnabled($hooksEnabled): void
    {
        $this->hooksEnabled = $hooksEnabled;
    }
}
