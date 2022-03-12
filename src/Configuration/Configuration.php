<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Configuration;

use Deployer\Exception\ConfigurationException;
use Deployer\Utility\Httpie;
use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\is_closure;
use function Deployer\Support\normalize_line_endings;

class Configuration implements \ArrayAccess
{
    /**
     * @var Configuration|null
     */
    private $parent;

    /**
     * @var array
     */
    private $values = [];

    public function __construct(Configuration $parent = null)
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

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): void
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

    /**
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
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
                    return $this->values[$name]= $this->parse($rawValue);
                }
            }
        }

        if (func_num_args() >= 2) {
            return $this->parse($default);
        }

        throw new ConfigurationException("Config option \"$name\" does not exist.");
    }

    /**
     * @return mixed|null
     */
    public function fetch(string $name)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if ($this->parent) {
            return $this->parent->fetch($name);
        }
        return null;
    }

    /**
     * @param string|mixed $value
     * @return string|mixed
     */
    public function parse($value)
    {
        if (is_string($value)) {
            $normalizedValue = normalize_line_endings($value);
            return preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', [$this, 'parseCallback'], $normalizedValue);
        }

        return $value;
    }

    public function ownValues(): array
    {
        return $this->values;
    }

    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * @param array $matches
     * @return mixed|null
     */
    private function parseCallback(array $matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     * @return mixed|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
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
        if (!$this->has('master_url')) {
            return;
        }

        $values = Httpie::get($this->get('master_url') . '/load')
            ->jsonBody([
                'host' => $this->get('alias'),
            ])
            ->getJson();
        $this->update($values);
    }

    public function save(): void
    {
        if (!$this->has('master_url')) {
            return;
        }

        Httpie::get($this->get('master_url') . '/save')
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
