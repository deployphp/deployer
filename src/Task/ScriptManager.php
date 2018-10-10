<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use function Deployer\Support\array_flatten;

class ScriptManager
{
    /**
     * @var TaskCollection
     */
    private $tasks;

    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run
     *
     * @param Host[] $hosts
     *
     * @return Task[]
     */
    public function getTasks(
        string $name,
        array $hosts = [],
        bool $hooksEnabled = true
    ): array {
        $collect = function (string $name) use (&$collect, $hosts, $hooksEnabled): array {
            $task = $this->tasks->get($name);

            if (!$task->shouldBePerformed(...array_values($hosts))) {
                return [];
            }

            $relatedTasks = [];

            if ($hooksEnabled) {
                $relatedTasks = array_merge(array_map($collect, $task->getBefore()), $relatedTasks);
            }

            if ($task instanceof GroupTask) {
                $relatedTasks = array_merge($relatedTasks, array_map($collect, $task->getGroup()));
            } else {
                $relatedTasks = array_merge($relatedTasks, [$task->getName()]);
            }

            if ($hooksEnabled) {
                $relatedTasks = array_merge($relatedTasks, array_map($collect, $task->getAfter()));
            }

            return $relatedTasks;
        };

        // Convert names to real tasks
        return array_map([$this->tasks, 'get'], array_flatten($collect($name)));
    }
}
