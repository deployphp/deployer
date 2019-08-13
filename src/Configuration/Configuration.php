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

class Configuration
{
    /**
     * @var Collection
     */
    private $collection;

    public function __construct()
    {
        $this->collection = new Collection();
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function set(string $name, $value)
    {
        $this->collection[$name] = $value;
    }

    public function add(string $name, array $array)
    {
        if ($this->has($name)) {
            $config = $this->get($name);
            if (!is_array($config)) {
                throw new ConfigurationException("Configuration parameter `$name` isn't array.");
            }
            $this->set($name, array_merge_alternate($config, $array));
        } else {
            $this->set($name, $array);
        }
    }

    public function get(string $name, $default = null)
    {
        if ($this->collection->has($name)) {
            if ($this->isClosure($this->collection[$name])) {
                $value = $this->collection[$name] = call_user_func($this->collection[$name]);
            } else {
                $value = $this->collection[$name];
            }
        } else {
            $config = Deployer::get()->config;

            if (isset($config[$name])) {
                if ($this->isClosure($config[$name])) {
                    $value = $this->collection[$name] = call_user_func($config[$name]);
                } else {
                    $value = $this->collection[$name] = $config[$name];
                }
            } else {
                if (null === $default) {
                    throw new ConfigurationException("Configuration parameter `$name` does not exist.");
                } else {
                    $value = $default;
                }
            }
        }

        return $this->parse($value);
    }

    public function has(string $name): bool
    {
        return $this->collection->has($name);
    }

    /**
     * Parse set values
     *
     * @param mixed $value
     * @return mixed
     */
    public function parse($value)
    {
        if (is_string($value)) {
            return preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', [$this, 'parseCallback'], $value);
        }

        return $value;
    }

    /**
     * Replace set values callback for parse
     *
     * @param string[] $matches
     * @return mixed
     */
    private function parseCallback(array $matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

    private function isClosure($var): bool
    {
        return is_object($var) && ($var instanceof \Closure);
    }
}
