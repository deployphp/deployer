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
            if ($stage === null || $task->isForStages($stage)) {
                if ($task instanceof GroupTask) {
                    return array_map($collect, $task->getTasks());
                } else {
                    return array_merge(
                        array_map($collect, $task->getBefore()),
                        [$name],
                        array_map($collect, $task->getAfter())
                    );
                }
            } else {
                return [];
            }
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
}
