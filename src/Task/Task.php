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
    private $sourceLocation = '';
    private $local = false;
    private $before = [];
    private $after = [];
    private $hidden = false;
    private $once = false;
    private $shallow = false;
    private $limit = null;
    private $selector = null;
    private $verbose = false;

    public function __construct($name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
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

    /**
     * @return $this
     */
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
     *
     * @return $this
     */
    public function local(bool $local = true)
    {
        $this->local = $local;
        return $this;
    }

    public function isLocal()
    {
        return $this->local;
    }

    /**
     * Mark this task to run only once on one of hosts.
     *
     * @return $this
     */
    public function once($once = true)
    {
        $this->once = $once;
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
    public function hidden(bool $hidden = true)
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * Make $task being run before this task.
     *
     * @return $this
     */
    public function addBefore(string $task)
    {
        array_unshift($this->before, $task);
        return $this;
    }

    /**
     * Make $task being run after this task
     *
     * @return $this
     */
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
     *
     * @return $this
     */
    public function shallow(bool $shallow = true)
    {
        $this->shallow = $shallow;
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
     *
     * @return $this
     */
    public function limit(?int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $selector
     *
     * @return $this
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

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * @return $this
     */
    public function verbose(bool $verbose = true)
    {
        $this->verbose = $verbose;
        return $this;
    }
}
