<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use Deployer\Host\Localhost;

class ScriptManager
{
    /**
     * @var TaskCollection
     */
    private $tasks;

    /**
     * @param TaskCollection $tasks
     */
    public function __construct(TaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * Return tasks to run
     *
     * @param string $name
     * @param Host[]|Localhost[] $hosts
     * @param bool $hooksEnabled
     * @return Task[]
     */
    public function getTasks($name, $hosts, $hooksEnabled = true)
    {
        $stages = [];
        $roles = [];
        $hostnames = [];

        foreach ($hosts as $hostname => $host) {
            $stages[] = $host->get('stage');
            $roles = array_merge($roles, $host->get('roles'));
            $hostnames[] = $hostname;
        }

        $collect = function ($name) use (&$collect, $stages, $roles, $hostnames, $hooksEnabled) {
            $task = $this->tasks->get($name);

            $onStage = $this->isOn($stages, $task);
            $onRoles = $this->isOn($roles, $task);
            $onHosts = $this->isOn($hostnames, $task);
            if (!$onStage || !$onRoles || !$onHosts) {
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

        $script = $collect($name);

        // Flatten
        $tasks = [];
        array_walk_recursive($script, function ($a) use (&$tasks) {
            $tasks[] = $a;
        });

        // Convert names to real tasks
        $tasks = array_map(function ($name) {
            return $this->tasks->get($name);
        }, $tasks);

        return $tasks;
    }

    private function isOn(array $list, Task $task)
    {
        foreach ($list as $name) {
            if (!$task->isOn($name)) {
                return false;
            }
        }
        return true;
    }
}
