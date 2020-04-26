<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Configuration;

use Deployer\Collection\Collection;
use Deployer\Deployer;
use Deployer\Exception\ConfigurationException;
use function Deployer\Support\array_merge_alternate;
use function Deployer\Support\is_closure;

class Configuration implements \ArrayAccess
{
    private $parent;
    private $values = [];

    public function __construct(Configuration $parent = null)
    {
        $this->parent = $parent;
    }

    public function set(string $name, $value)
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

    public function add(string $name, array $array)
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

        if ($default !== null) {
            return $this->parse($default);
        }

        throw new ConfigurationException("Config option \"$name\" does not exist.");
    }

    protected function fetch($name)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
        if ($this->parent) {
            return $this->parent->fetch($name);
        }
        return null;
    }

    public function parse($value)
    {
        if (is_string($value)) {
            return preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', [$this, 'parseCallback'], $value);
        }

        return $value;
    }

    private function parseCallback(array $matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    public function persist()
    {
        $values = [];
        if ($this->parent !== null) {
            $values = $this->parent->persist();
        }
        foreach ($this->values as $key => $value) {
            if (is_closure($value)) {
                continue;
            }
            $values[$key] = $value;
        }
        return $values;
    }

    public function load()
    {
        $file = $this->configFile();
        if (file_exists($file)) {
            $this->values = json_decode(file_get_contents($file), true);
        }
    }

    public function save()
    {
        file_put_contents($this->configFile(), json_encode($this->persist()));
    }

    private function configFile() {
        return sprintf('%s/%s.dep', $this->get('config_directory'), str_replace(DIRECTORY_SEPARATOR, "_", $this->get('alias')));
    }
}
