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

    /**
     * Task constructor.
     * @param mixed $name
     */
    public function __construct($name, callable $callback = null)
    {
        $this->name = $name;
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

    /**
     * @return mixed
     */
    public function getName()
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
     * Mark this task local.
     */
    public function local(bool $local = true): self
    {
        $this->local = $local;
        return $this;
    }

    public function isLocal(): bool
    {
        return $this->local;
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

    /**
     * Sets task as shallow. Shallow task will not print execution message/finish messages.
     */
    public function shallow(bool $shallow = true): self
    {
        $this->shallow = $shallow;
        return $this;
    }

    public function isShallow(): bool
    {
        return $this->shallow;
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
}
