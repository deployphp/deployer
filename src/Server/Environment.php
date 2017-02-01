<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Collection\Collection;
use Deployer\Deployer;

class Environment
{
    /**
     * Array of set values.
     * @var Collection
     */
    private $values = null;

    /**
     * Values represented by their keys here are protected, and cannot be
     * changed by calling the `set` method.
     * @var array
     */
    private $protectedNames = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->values = new Collection();
    }

    /**
     * @return Collection
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param string $name
     * @param bool|int|string|array $value
     */
    public function set($name, $value)
    {
        $this->checkIfNameIsProtected($name);
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @param bool|int|string|array $value
     */
    public function setAsProtected($name, $value)
    {
        $this->set($name, $value);
        $this->protectedNames[] = $name;
    }

    /**
     * Checks whether the given name was registered as protected, or if there is
     * a protected parameter which would be overwritten.
     * @param string $name
     * @throws \RuntimeException if the value already exists and is protected.
     * @throws \RuntimeException if there's a protected parameter which would
     * be overwritten.
     */
    private function checkIfNameIsProtected($name)
    {
        $length = strlen($name);

        foreach ($this->protectedNames as $protectedName) {
            $len = strlen($protectedName);
            if ($name === $protectedName) {
                throw new \RuntimeException("The parameter `$name` cannot be set, because it's protected.");
            } elseif ($len < $length && '.' === $name[$len] && 0 === strpos($name, $protectedName)) {
                throw new \RuntimeException("The parameter `$name` cannot be set, because `$protectedName` is protected.");
            } elseif ($len > $length && '.' === $protectedName[$length] && 0 === strpos($protectedName, $name)) {
                throw new \RuntimeException("The parameter `$name` could not be set, because a protected parameter named `$protectedName` already exists.");
            }
        }
    }

    /**
     * @param string $name
     * @param array $array
     */
    public function add($name, $array)
    {
        if ($this->has($name)) {
            $config = $this->get($name);
            if (!is_array($config)) {
                throw new \RuntimeException("Configuration parameter `$name` isn't array.");
            }
            $this->set($name, array_merge_recursive($config, $array));
        } else {
            $this->set($name, $array);
        }
    }

    /**
     * @param string $name
     * @param bool|int|string|array $default
     * @return bool|int|string|array
     * @throws \RuntimeException
     */
    public function get($name, $default = null)
    {
        if ($this->values->has($name)) {
            if ($this->isClosure($this->values[$name])) {
                $value = $this->values[$name] = call_user_func($this->values[$name]);
            } else {
                $value = $this->values[$name];
            }
        } else {
            $config = Deployer::get()->config;

            if (isset($config[$name])) {
                if ($this->isClosure($config[$name])) {
                    $value = $this->values[$name] = call_user_func($config[$name]);
                } else {
                    $value = $this->values[$name] = $config[$name];
                }
            } else {
                if (null === $default) {
                    throw new \RuntimeException("Configuration parameter `$name` does not exists.");
                } else {
                    $value = $default;
                }
            }
        }

        return $this->parse($value);
    }

    /**
     * Checks if set var exists.
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->values->has($name);
    }

    /**
     * Parse set values.
     *
     * @param string $value
     * @return string
     */
    public function parse($value)
    {
        if (is_string($value)) {
            $value = preg_replace_callback('/\{\{\s*([\w\.\/]+)\s*\}\}/', [$this, 'parseCallback'], $value);
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
