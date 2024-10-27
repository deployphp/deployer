<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\ConfigurationException;
use Deployer\Utility\Httpie;

use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\is_closure;
use function Deployer\Support\normalize_line_endings;

class Configuration implements \ArrayAccess
{
    private ?Configuration $parent;
    private array $values = [];

    public function __construct(?Configuration $parent = null)
    {
        $this->parent = $parent;
    }

    public function update(array $values): void
    {
        $this->values = array_merge($this->values, $values);
    }

    public function bind(Configuration $parent): void
    {
        $this->parent = $parent;
    }

    public function set(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    public function has(string $name): bool
    {
        $ok = array_key_exists($name, $this->values);
        if ($ok) {
            return true;
        }
        if ($this->parent) {
            return $this->parent->has($name);
        }
        return false;
    }

    public function hasOwn(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    public function add(string $name, array $array): void
    {
        if ($this->has($name)) {
            $config = $this->get($name);
            if (!is_array($config)) {
                throw new ConfigurationException("Config option \"$name\" isn't array.");
            }
            $this->set($name, array_merge_alternate($config, $array));
        } else {
            $this->set($name, $array);
        }
    }

    public function get(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->values)) {
            if (is_closure($this->values[$name])) {
                return $this->values[$name] = $this->parse(call_user_func($this->values[$name]));
            } else {
                return $this->parse($this->values[$name]);
            }
        }

        if ($this->parent) {
            $rawValue = $this->parent->fetch($name);
            if ($rawValue !== null) {
                if (is_closure($rawValue)) {
                    return $this->values[$name] = $this->parse(call_user_func($rawValue));
                } else {
                    return $this->values[$name] = $this->parse($rawValue);
                }
            }
        }

        if (func_num_args() >= 2) {
            return $this->parse($default);
        }

        throw new ConfigurationException("Config option \"$name\" does not exist.");
    }

    protected function fetch(string $name): mixed
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if ($this->parent) {
            return $this->parent->fetch($name);
        }
        return null;
    }

    public function parse(mixed $value): mixed
    {
        if (is_string($value)) {
            $normalizedValue = normalize_line_endings($value);
            return preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', function (array $matches) {
                return $this->get($matches[1]);
            }, $normalizedValue);
        }

        return $value;
    }

    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * @param string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }

    public function load(): void
    {
        if (!Deployer::isWorker()) {
            return;
        }

        $values = Httpie::get(MASTER_ENDPOINT . '/load')
            ->setopt(CURLOPT_CONNECTTIMEOUT, 0)
            ->setopt(CURLOPT_TIMEOUT, 0)
            ->jsonBody([
                'host' => $this->get('alias'),
            ])
            ->getJson();
        $this->update($values);
    }

    public function save(): void
    {
        if (!Deployer::isWorker()) {
            return;
        }

        Httpie::get(MASTER_ENDPOINT . '/save')
            ->setopt(CURLOPT_CONNECTTIMEOUT, 0)
            ->setopt(CURLOPT_TIMEOUT, 0)
            ->jsonBody([
                'host' => $this->get('alias'),
                'config' => $this->persist(),
            ])
            ->getJson();
    }

    public function persist(): array
    {
        $values = [];
        foreach ($this->values as $key => $value) {
            if (is_closure($value)) {
                continue;
            }
            $values[$key] = $value;
        }
        return $values;
    }
}
