<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Selector\Selector;

class Task
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var callable|null
     */
    private $callback;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $sourceLocation = '';
    /**
     * @var array
     */
    private $before = [];
    /**
     * @var array
     */
    private $after = [];
    /**
     * @var bool
     */
    private $hidden = false;
    /**
     * @var bool
     */
    private $once = false;
    /**
     * @var bool
     */
    private $oncePerNode = false;
    /**
     * @var int|null
     */
    private $limit = null;
    /**
     * @var array|null
     */
    private $selector = null;
    /**
     * @var bool
     */
    private $verbose = false;
    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @param callable():void $callback
     */
    public function __construct(string $name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * @param callable():void $callback
     */
    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function run(Context $context): void
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

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function desc(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getSourceLocation(): string
    {
        return $this->sourceLocation;
    }

    public function setSourceLocation(string $path): void
    {
        $this->sourceLocation = $path;
    }

    public function saveSourceLocation(): void
    {
        if (function_exists('debug_backtrace')) {
            $trace = debug_backtrace();
            $this->sourceLocation = $trace[1]['file'];
        }
    }

    /**
     * Mark this task to run only once on one of hosts.
     */
    public function once(bool $once = true): self
    {
        $this->once = $once;
        return $this;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }

    /**
     * Mark task to only run once per node.
     * Node is a group of hosts with same hostname or with same node label.
     */
    public function oncePerNode(bool $once = true): self
    {
        $this->oncePerNode = $once;
        return $this;
    }

    public function isOncePerNode(): bool
    {
        return $this->oncePerNode;
    }

    /**
     * Mark task as hidden and not accessible from CLI.
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Make $task being run before this task.
     */
    public function addBefore(string $task): self
    {
        array_unshift($this->before, $task);
        return $this;
    }

    /**
     * Make $task being run after this task
     */
    public function addAfter(string $task): self
    {
        array_push($this->after, $task);
        return $this;
    }

    public function getBefore(): array
    {
        return $this->before;
    }

    public function getAfter(): array
    {
        return $this->after;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function limit(?int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function select(string $selector): self
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

    public function addSelector(?array $newSelector): void
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

    public function verbose(bool $verbose = true): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }
}
