<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Exception\Exception;
use function Deployer\Support\array_flatten;

class ScriptManager
{
    private $tasks;
    private $hooksEnabled = true;
    private $startFrom = null;

    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run.
     *
     * @return Task[]
     */
    public function getTasks(string $name, ?string $startFrom = null): array
    {
        $tasks = [];
        $allTasks = $this->doGetTasks($name);

        if ($startFrom === null) {
            $tasks = $allTasks;
        } else {
            $skip = true;
            foreach ($allTasks as $task) {
                if ($skip) {
                    if ($task->getName() === $startFrom) {
                        $skip = false;
                    } else {
                        continue;
                    }
                }
                $tasks[] = $task;
            }
            if (count($tasks) === 0) {
                throw new Exception('All tasks skipped via --start-from option. Nothing to run.');
            }
        }
        return $tasks;
    }

    /**
     * @return Task[]
     */
    public function doGetTasks(string $name): array
    {
        $tasks = [];

        $task = $this->tasks->get($name);

        if ($this->hooksEnabled) {
            $tasks = array_merge(array_map([$this, 'doGetTasks'], $task->getBefore()), $tasks);
        }

        if ($task instanceof GroupTask) {
            foreach ($task->getGroup() as $taskName) {
                $subTasks = $this->doGetTasks($taskName);
                foreach ($subTasks as $subTask) {
                    $subTask->addSelector($task->getSelector());
                    $tasks[] = $subTask;
                }
            }
        } else {
            $tasks[] = $task;
        }

        if ($this->hooksEnabled) {
            $tasks = array_merge($tasks, array_map([$this, 'doGetTasks'], $task->getAfter()));
        }

        return array_flatten($tasks);
    }

    public function getHooksEnabled(): bool
    {
        return $this->hooksEnabled;
    }

    public function setHooksEnabled(bool $hooksEnabled): void
    {
        $this->hooksEnabled = $hooksEnabled;
    }
}
