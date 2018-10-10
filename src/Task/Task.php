<?php declare(strict_types=1);
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
     * @var null|callable
     */
    private $callback;

    /**
     * @var null|string
     */
    private $description;

    /**
     * Should we run this task locally?
     *
     * @var bool
     */
    private $local = false;

    /**
     * Lists of stages there task should be executed.
     *
     * @var string[]
     */
    private $onStages = [];

    /**
     * Lists of roles there task should be executed.
     *
     * @var string[]
     */
    private $onRoles = [];

    /**
     * Lists of hosts there task should be executed.
     *
     * @var string[]
     */
    private $onHosts = [];

    /**
     * List of task names to run before.
     *
     * @var string[]
     */
    private $before = [];

    /**
     * List of task names to run after.
     *
     * @var string[]
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

    public function __construct(string $name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * @return void
     */
    public function run(Context $context)
    {
        Context::push($context);

        if ($this->callback !== null) {
            // Call task
            call_user_func($this->callback);
        }

        if ($this->once) {
            $this->hasRun = true;
        }

        // Clear working_path
        if ($context->getConfig() !== null) {
            $context->getConfig()->set('working_path', false);
        }

        Context::pop();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function desc(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Mark this task local
     *
     * @return static
     */
    public function local()
    {
        $this->local = true;
        return $this;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function once(): self
    {
        $this->once = true;
        return $this;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }

    public function onHosts(string ...$hosts): self
    {
        $this->onHosts = $hosts;
        return $this;
    }

    public function onRoles(string ...$roles): self
    {
        $this->onRoles = $roles;
        return $this;
    }

    public function onStage(string ...$stages): self
    {
        $this->onStages = $stages;
        return $this;
    }

    /**
     * Checks what task should be performed on one of hosts.
     */
    public function shouldBePerformed(Host ...$hosts): bool
    {
        // don't allow to run again it the task has been marked to run only once
        if ($this->once && $this->hasRun) {
            return false;
        }

        foreach ($hosts as $host) {
            $onHost = empty($this->onHosts) || in_array($host->getHostname(), $this->onHosts, true);

            $onRole = empty($this->onRoles);
            foreach ((array) $host->get('roles', []) as $role) {
                if (in_array($role, $this->onRoles, true)) {
                    $onRole = true;
                }
            }

            $onStage = empty($this->onStages);
            if ($host->has('stage')) {
                if (in_array($host->get('stage'), $this->onStages, true)) {
                    $onStage = true;
                }
            }

            if ($onHost && $onRole && $onStage) {
                return true;
            }
        }

        return empty($hosts);
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * Mark task as private
     */
    public function setPrivate(): self
    {
        $this->private = true;
        return $this;
    }

    /**
     * @param string $task
     *
     * @return static
     */
    public function addBefore(string $task)
    {
        array_unshift($this->before, $task);
        return $this;
    }

    /**
     * @param string $task
     *
     * @return static
     */
    public function addAfter(string $task)
    {
        array_push($this->after, $task);
        return $this;
    }

    /**
     * Get before tasks names.
     *
     * @return string[]
     */
    public function getBefore(): array
    {
        return $this->before;
    }

    /**
     * Get after tasks names.
     *
     * @return string[]
     */
    public function getAfter(): array
    {
        return $this->after;
    }

    /**
     * Sets task shallow.
     *
     * Shallow task will not print execution message/finish messages.
     */
    public function shallow(): self
    {
        $this->shallow = true;
        return $this;
    }

    public function isShallow(): bool
    {
        return $this->shallow;
    }
}
