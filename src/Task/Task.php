<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Selector\Selector;

class Task
{
    private $name;
    private $callback;
    private $description;

    /**
     * Task source file location.
     *
     * @var string
     */
    private $sourceLocation = '';

    /**
     * Should we run this task locally?
     *
     * @var bool
     */
    private $local = false;

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
    private $hidden = false;

    /**
     * Run task only once on one of hosts.
     *
     * @var bool
     */
    private $once = false;

    /**
     * Shallow task will not print execution message/finish messages.
     * Useful for success messages and info printing.
     *
     * @var bool
     */
    private $shallow = false;

    /**
     * Limit parallel execution of the task.
     *
     * @var int|null
     */
    private $limit = null;

    /**
     * @var array
     */
    private $selector;

    public function __construct($name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
        $this->selector = Selector::parse('all');
    }

    public function run(Context $context)
    {
        Context::push($context);

        try {
            call_user_func($this->callback); // call task
        } finally {
            if ($context->getConfig() !== null) {
                $context->getConfig()->set('working_path', null);
            }

            Context::pop();
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function desc(string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getSourceLocation(): string
    {
        return $this->sourceLocation;
    }

    public function saveSourceLocation()
    {
        if (function_exists('debug_backtrace')) {
            $trace = debug_backtrace();
            $this->sourceLocation = $trace[1]['file'];
        }
    }

    /**
     * Mark this task local.
     */
    public function local()
    {
        $this->local = true;
        return $this;
    }

    public function isLocal()
    {
        return $this->local;
    }

    /**
     * Mark this task to run only once on one of hosts.
     */
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
     * Mark task as hidden and not accessible from CLI.
     *
     * @return $this
     */
    public function hidden()
    {
        $this->hidden = true;
        return $this;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function addBefore(string $task)
    {
        array_unshift($this->before, $task);
        return $this;
    }

    public function addAfter(string $task)
    {
        array_push($this->after, $task);
        return $this;
    }

    public function getBefore()
    {
        return $this->before;
    }

    public function getAfter()
    {
        return $this->after;
    }

    /**
     * Sets task as shallow. Shallow task will not print execution message/finish messages.
     */
    public function shallow()
    {
        $this->shallow = true;
        return $this;
    }

    public function isShallow()
    {
        return $this->shallow;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     * @return Task
     */
    public function limit(?int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $selector
     * @return Task
     */
    public function select(string $selector)
    {
        $this->selector = Selector::parse($selector);
        return $this;
    }

    /**
     * @return array
     */
    public function getSelector(): ?array
    {
        return $this->selector;
    }

    public function addSelector(?array $newSelector)
    {
        if ($newSelector !== null) {
            if ($this->selector === null) {
                $this->selector = $newSelector;
            } else {
                $this->selector = array_merge($this->selector, $newSelector);
            }
        }
    }
}
