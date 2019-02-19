<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use function Deployer\Support\array_flatten;

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
    private $on = ['hosts' => [], 'roles' => [], 'stages' => []];

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
     * Mark task to run only once, of the first node from the pool
     *
     * @var bool
     */
    private $once = false;

    /**
     * Mark if the task has run at least once
     *
     * @var bool
     */
    private $hasRun = false;

    /**
     * Shallow task will not print execution message/finish messages.
     * Useful for success messages and info printing.
     *
     * @var bool
     */
    private $shallow = false;

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

        if ($this->once) {
            $this->hasRun = true;
        }

        // Clear working_path
        if ($context->getConfig() !== null) {
            $context->getConfig()->set('working_path', false);
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

    public function once()
    {
        $this->once = true;
        return $this;
    }

    public function isOnce()
    {
        return $this->once;
    }

    /**
     * @param array $hosts
     * @return $this
     */
    public function onHosts(...$hosts)
    {
        $this->on['hosts'] = array_flatten($hosts);
        return $this;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function onRoles(...$roles)
    {
        $this->on['roles'] = array_flatten($roles);
        return $this;
    }

    /**
     * @param array $stages
     * @return $this
     */
    public function onStage(...$stages)
    {
        $this->on['stages'] = array_flatten($stages);
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
        // don't allow to run again it the task has been marked to run only once
        if ($this->once && $this->hasRun) {
            return false;
        }

        foreach ($hosts as $host) {
            $onHost = empty($this->on['hosts']) || in_array($host->getHostname(), $this->on['hosts'], true);

            $onRole = empty($this->on['roles']);
            foreach ((array) $host->get('roles', []) as $role) {
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
     *
     * @return $this
     */
    public function addBefore(string $task)
    {
        array_unshift($this->before, $task);
        return $this;
    }

    /**
     * @param string $task
     *
     * @return $this
     */
    public function addAfter(string $task)
    {
        array_push($this->after, $task);
        return $this;
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
     * Sets task shallow.
     *
     * Shallow task will not print execution message/finish messages.
     *
     * @return $this
     */
    public function shallow()
    {
        $this->shallow = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShallow()
    {
        return $this->shallow;
    }

    /**
     * @internal this is used by ParallelExecutor and prevent multiple run
     */
    public function setHasRun()
    {
        if ($this->isOnce()) {
            $this->hasRun = true;
        }
    }
}
