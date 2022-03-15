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
    /**
     * @var TaskCollection
     */
    private $tasks;
    /**
     * @var bool
     */
    private $hooksEnabled = true;
    /**
     * @var array
     */
    private $visitedTasks = [];

    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run.
     *
     * @return Task[]
     */
    public function getTasks(string $name, ?string $startFrom = null, array &$skipped = []): array
    {
        $tasks = [];
        $this->visitedTasks = [];
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
                        $skipped[] = $task->getName();
                        continue;
                    }
                }
                $tasks[] = $task;
            }
            if (count($tasks) === 0) {
                throw new Exception('All tasks skipped via --start-from option. Nothing to run.');
            }
        }

        $enabledTasks = [];
        foreach ($tasks as $task) {
            if ($task->isEnabled()) {
                $enabledTasks[] = $task;
            }
        }

        return $enabledTasks;
    }

    /**
     * @return Task[]
     */
    public function doGetTasks(string $name): array
    {
        if (array_key_exists($name, $this->visitedTasks)) {
            if ($this->visitedTasks[$name] >= 100) {
                throw new Exception("Looks like a circular dependency with \"$name\" task.");
            }
            $this->visitedTasks[$name]++;
        } else {
            $this->visitedTasks[$name] = 1;
        }

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
                    if ($task->isOnce()) {
                        $subTask->once();
                    }
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
