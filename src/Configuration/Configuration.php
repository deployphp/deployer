<?php
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
    private $collection = null;

    public function __construct()
    {
        $this->collection = new Collection();
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param Collection $collection
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param string $name
     * @param bool|int|string|array $value
     */
    public function set($name, $value)
    {
        $this->collection[$name] = $value;
    }

    /**
     * @param string $name
     * @param array $array
     */
    public function add($name, array $array)
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

    /**
     * @param string $name
     * @param bool|int|string|array $default
     * @return bool|int|string|array
     */
    public function get($name, $default = null)
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
                    throw new ConfigurationException("Configuration parameter `$name` does not exists.");
                } else {
                    $value = $default;
                }
            }
        }

        return $this->parse($value);
    }

    /**
     * Checks if set var exists
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->collection->has($name);
    }

    /**
     * Parse set values
     *
     * @param string $value
     * @return string
     */
    public function parse($value)
    {
        if (is_string($value)) {
            $value = preg_replace_callback('/\{\{\s*([\w\.\/-]+)\s*\}\}/', [$this, 'parseCallback'], $value);
        }

        return $value;
    }

    /**
     * Replace set values callback for parse
     *
     * @param array $matches
     * @return mixed
     */
    private function parseCallback($matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

    /**
     * @param mixed $t
     * @return bool
     */
    private function isClosure($t)
    {
        return is_object($t) && ($t instanceof \Closure);
    }
}
