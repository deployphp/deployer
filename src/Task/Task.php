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
     * @var callable
     */
    private $callback;

    /**
     * @var string
     */
    private $description;

    /**
     * Should we run this task locally?
     *
     * @var bool
     */
    private $local = false;

    /**
     * List of hostnames, roles, stages there task should be executed.
     *
     * @var array
     */
    private $on = [];

    /**
     * List of task names to run before.
     *
     * @var array
     */
    private $before = [];

    /**
     * List of task names to run after.
     *
     * @var array
     */
    private $after = [];

    /**
     * Make task internal and not visible in CLI.
     *
     * @var bool
     */
    private $private = false;

    /**
     * @param string $name Tasks name
     * @param callable $callback Task code
     */
    public function __construct($name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * @param Context $context
     */
    public function run(Context $context)
    {
        Context::push($context);

        // Call task
        call_user_func($this->callback);

        // Clear working_path
        $config = $context->getConfiguration();
        if ($config !== null) {
            $config->set('working_path', false);
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
     * @param string $description
     * @return $this
     */
    public function desc($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Mark this task local
     *
     * @return $this
     */
    public function local()
    {
        $this->local = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return $this->local;
    }

    /**
     * @param array $list
     * @return $this
     */
    public function on(...$list)
    {
        $this->on = $list;
        return $this;
    }

    /**
     * @return array
     */
    public function getOn()
    {
        return $this->on;
    }

    /**
     * Decide to run or not to run on this task
     *
     * @param string $name
     * @return bool
     */
    public function isOn(string $name)
    {
        if (empty($this->on)) {
            return true;
        } else {
            return in_array($name, $this->on, true);
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
     * Mark task as private
     *
     * @return $this
     */
    public function setPrivate()
    {
        $this->private = true;
        return $this;
    }

    /**
     * @param string $task
     */
    public function addBefore(string $task)
    {
        array_unshift($this->before, $task);
    }

    /**
     * @param string $task
     */
    public function addAfter(string $task)
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
