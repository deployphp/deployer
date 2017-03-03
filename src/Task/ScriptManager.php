<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

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
     * ScriptManager constructor.
     * @param TaskCollection $tasks
     */
    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run.
     *
     * @param string $name
     * @param string $stage
     * @return Task[]
     */
    public function getTasks($name, $stage = null)
    {
        $collect = function ($name) use (&$collect, $stage) {
            $task = $this->tasks->get($name);
            if ($stage !== null && !$task->isForStages($stage)) {
                return [];
            }

            $relatedTasks = [];

            if ($this->isHooksEnabled()) {
                $relatedTasks = array_merge(array_map($collect, $task->getBefore()), $relatedTasks);
            }

            if ($task instanceof GroupTask) {
                $relatedTasks = array_merge($relatedTasks, array_map($collect, $task->getGroup()));
            } else {
                $relatedTasks = array_merge($relatedTasks, [$task->getName()]);
            }

            if ($this->isHooksEnabled()) {
                $relatedTasks = array_merge($relatedTasks, array_map($collect, $task->getAfter()));
            }

            return $relatedTasks;
        };

        $script = $collect($name);

        // Flatten
        $tasks = [];
        array_walk_recursive($script, function ($a) use (&$tasks) {
            $tasks[] = $a;
        });

        // Convert names to real strings.
        $tasks = array_map(function ($name) {
            return $this->tasks->get($name);
        }, $tasks);

        return $tasks;
    }

    /**
     * @return bool
     */
    public function isHooksEnabled()
    {
        return $this->hooksEnabled;
    }

    /**
     * @param bool $hooksEnabled
     */
    public function setHooksEnabled($hooksEnabled)
    {
        $this->hooksEnabled = $hooksEnabled;
    }
}
