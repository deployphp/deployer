<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

use Countable;
use IteratorAggregate;

class Collection implements Countable, IteratorAggregate
{
    /**
     * @var array
     */
    protected $values = [];

    public function all(): array
    {
        return $this->values;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
    {
        if ($this->has($name)) {
            return $this->values[$name];
        } else {
            $this->throwNotFound($name);
        }
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * @param mixed $object
     */
    public function set(string $name, $object)
    {
        $this->values[$name] = $object;
    }

    public function remove(string $name): void
    {
        if ($this->has($name)) {
            unset($this->values[$name]);
        } else {
            $this->throwNotFound($name);
        }
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function select(callable $callback): array
    {
        $values = [];

        foreach ($this->values as $key => $value) {
            if ($callback($value, $key)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }

    protected function throwNotFound(string $name): void
    {
        throw new \InvalidArgumentException("Element \"$name\" not found in collection.");
    }
}
