<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Symfony\Component\Console\Input\InputOption;

abstract class AbstractTask implements TaskInterface
{
    /**
     * @var string
     */
    protected $description;

    protected $options = [];

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
    public function desc($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     * @deprecated Use desc method instead of.
     */
    public function description($description)
    {
        return $this->desc($description);
    }

    public function option($name, $short = null, $description = '', $default = null)
    {
        $this->options[$name] = new InputOption($name, $short, InputOption::VALUE_OPTIONAL, $description, $default);
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
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
     * Get before runners.
     * @return Runner[]
     */
    protected function getBefore()
    {
        $runners = [];
        foreach ($this->beforeTasks as $task) {
            $runners = array_merge($runners, $task->get());
        }
        return $runners;
    }

    /**
     * Get after runners
     * @return Runner[]
     */
    protected function getAfter()
    {
        $runners = [];
        foreach ($this->afterTasks as $task) {
            $runners = array_merge($runners, $task->get());
        }
        return $runners;
    }
} 