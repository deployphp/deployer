<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class Task
{
    /**
     * @var string
     */
    private $name;

    /**
     * Task code.
     * @var callable
     */
    private $callback;

    /**
     * Task description.
     * @var string
     */
    private $description;

    /**
     * Should run this task only once and locally?
     * @var bool
     */
    private $once = false;

    /**
     * List of stages in which this task should be executed.
     * @var array  Key contains stage names.
     */
    private $onlyForStage = [];

    /**
     * List of servers names there this task should be executed.
     * @var array  Key contains server names.
     */
    private $onlyOn = [];

    /**
     * List of task names to run before.
     * @var array
     */
    private $before = [];

    /**
     * List of task names to run after.
     * @var array
     */
    private $after = [];

    /**
     * Make task internal and not visible in CLI.
     * @var bool
     */
    private $private = false;

    /**
     * @param string $name Tasks name
     * @param callable $callback Task code.
     */
    public function __construct($name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * Run task.
     *
     * @param Context $context
     */
    public function run(Context $context)
    {
        Context::push($context);

        // Call task
        call_user_func($this->callback);

        // Clear working_path
        $env = $context->getEnvironment();
        if ($env !== null) {
            $env->set('working_path', false);
        }

        Context::pop();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set task description.
     * @param string $description
     * @return Task
     */
    public function desc($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set this task local and run only once.
     * @return Task
     */
    public function once()
    {
        $this->once = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnce()
    {
        return $this->once;
    }

    /**
     * @param array|string $servers
     * @return Task
     */
    public function onlyOn($servers = [])
    {
        $this->onlyOn = array_flip(is_array($servers) ? $servers : func_get_args());
        return $this;
    }

    /**
     * Indicate for which stages this task should be run.
     *
     * @param array|string $stages
     * @return Task
     */
    public function onlyForStage($stages = [])
    {
        $this->onlyForStage = array_flip(is_array($stages) ? $stages: func_get_args());
        return $this;
    }

    /**
     * @return array
     */
    public function getOnlyOn()
    {
        return $this->onlyOn;
    }

    /**
     * @return array
     */
    public function getOnlyForStage()
    {
        return $this->onlyForStage;
    }

    /**
     * Decide to run or not to run for these stages.
     * @param $stages
     * @return bool
     */
    public function isForStages($stages)
    {
        if (empty($this->onlyForStage)) {
            return true;
        } else {
            return count(array_intersect((array)$stages, array_keys($this->onlyForStage))) > 0;
        }
    }

    /**
     * Decide to run or not to run on this server.
     * @param string $serverName
     * @return bool
     */
    public function isOnServer($serverName)
    {
        if (empty($this->onlyOn)) {
            return true;
        } else {
            return array_key_exists($serverName, $this->onlyOn);
        }
    }

    /**
     * @return boolean
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Mark task as private.
     * @return Task
     */
    public function setPrivate()
    {
        $this->private = true;
        return $this;
    }

    /**
     * @param string $task
     */
    public function addBefore($task)
    {
        array_unshift($this->before, $task);
    }

    /**
     * @param string $task
     */
    public function addAfter($task)
    {
        array_push($this->after, $task);
    }

    /**
     * Get before tasks names.
     * @return string[]
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Get after tasks names.
     * @return string[]
     */
    public function getAfter()
    {
        return $this->after;
    }
}
