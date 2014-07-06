<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

abstract class AbstractTask implements TaskInterface
{
    /**
     * @var string
     */
    protected $description;

    /**
     * @var TaskInterface[]
     */
    protected $afterTasks = [];

    /**
     * @var TaskInterface[]
     */
    protected $beforeTasks = [];

    /**
     * Set task description
     * @param string $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Description of task.
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Print description.
     */
    public function printDescription()
    {
        if (!output()->isQuiet() && !empty($this->description)) {
            writeln("<info>{$this->description}</info>");
        }
    }

    /**
     * @param TaskInterface $task
     */
    public function before(TaskInterface $task)
    {
        $this->beforeTasks[] = $task;
    }

    /**
     * @param TaskInterface $task
     */
    public function after(TaskInterface $task)
    {
        $this->afterTasks[] = $task;
    }

    /**
     * Run before tasks.
     */
    protected function runBeforeTasks()
    {
        foreach ($this->beforeTasks as $task) {
            $task->run();
        }
    }

    /**
     * Run after tasks.
     */
    protected function runAfterTasks()
    {
        foreach ($this->afterTasks as $task) {
            $task->run();
        }
    }
} 