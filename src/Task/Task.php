<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;

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
     * Lists of hosts, roles, stages there task should be executed.
     *
     * @var array
     */
    private $on = ['hosts' => [], 'roles' => [], 'stages' => [], 'conditions' => []];

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

        if ($this->canRun($context)) {
            // Call task
            call_user_func($this->callback);

            // Clear working_path
            if ($context->getConfig() !== null) {
                $context->getConfig()->set('working_path', false);
            }
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
     * @param array $hosts
     * @return $this
     */
    public function onHosts(...$hosts)
    {
        $this->on['hosts'] = $hosts;
        return $this;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function onRoles(...$roles)
    {
        $this->on['roles'] = $roles;
        return $this;
    }

    /**
     * @param array $stages
     * @return $this
     */
    public function onStage(...$stages)
    {
        $this->on['stages'] = $stages;
        return $this;
    }

    /**
     * @param array ...$conditions
     * @return $this
     */
    public function onCondition(...$conditions) {
        $this->on['conditions'] = $conditions;
        return $this;
    }

    /**
     * Checks what task should be performed on one of hosts.
     *
     * @param Host[] $hosts
     * @return bool
     */
    public function shouldBePerformed(...$hosts)
    {
        foreach ($hosts as $host) {
            $onHost = empty($this->on['hosts']) || in_array($host->getHostname(), $this->on['hosts'], true);

            $onRole = empty($this->on['roles']);
            foreach ($host->get('roles', []) as $role) {
                if (in_array($role, $this->on['roles'], true)) {
                    $onRole = true;
                }
            }

            $onStage = empty($this->on['stages']);
            if ($host->has('stage')) {
                if (in_array($host->get('stage'), $this->on['stages'], true)) {
                    $onStage = true;
                }
            }

            if ($onHost && $onRole && $onStage) {
                return true;
            }
        }

        return empty($hosts);
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

    /**
     * Check can run based on conditions under the current context
     *
     * @param Context $context
     * @return bool
     */
    protected function canRun(Context $context)
    {
        foreach ($this->on['conditions'] as $condition) {
            $onCondition = false;
            if (is_string($condition)) {
                $config = $context->getConfig();
                if ($config !== null) {
                    $onCondition = $config->get($condition, false);
                }
            } elseif (is_callable($condition)) {
                $onCondition = call_user_func($condition);
            } else {
                $onCondition = $condition;
            }
            if (!$onCondition) {
                return false;
            }
        }
        return true;
    }
}
