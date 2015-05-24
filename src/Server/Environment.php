<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Type\DotArray;

class Environment
{
    const DEPLOY_PATH = 'deploy_path';

    /**
     * Globally defaults values.
     *
     * @var \Deployer\Type\DotArray
     */
    static private $defaults = null;

    /**
     * Array of env values.
     * @var \Deployer\Type\DotArray
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
        $this->values = new DotArray();
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
     * @param bool|int|string|array $default
     * @return bool|int|string|array
     * @throws \RuntimeException
     */
    public function get($name, $default = null)
    {
        if ($this->values->hasKey($name)) {
            $value = $this->values[$name];
        } else {
            if (null !== self::$defaults && isset(self::$defaults[$name])) {
                if (is_callable(self::$defaults[$name])) {
                    $value = $this->values[$name] = call_user_func(self::$defaults[$name]);
                } else {
                    $value = $this->values[$name] = self::$defaults[$name];
                }
            } else {
                if (null === $default) {
                    throw new \RuntimeException("Environment parameter `$name` does not exists.");
                } else {
                    $value = $default;
                }
            }
        }

        return $this->parse($value);
    }

    /**
     * Checks if env var exists.
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->values->hasKey($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function getDefault($name)
    {
        if (null === self::$defaults) {
            self::$defaults = new DotArray();
        }
        return self::$defaults[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public static function setDefault($name, $value)
    {
        if (null === self::$defaults) {
            self::$defaults = new DotArray();
        }
        self::$defaults[$name] = $value;
    }

    /**
     * Parse env values.
     *
     * @param string $value
     * @return string
     */
    public function parse($value)
    {
        if (is_string($value)) {
            $value = preg_replace_callback('/\{\{\s*([\w\.]+)\s*\}\}/', [$this, 'parseCallback'], $value);
        }

        return $value;
    }

    /**
     * Replace env values callback for parse
     *
     * @param array $matches
     * @return mixed
     */
    private function parseCallback($matches)
    {
        return isset($matches[1]) ? $this->get($matches[1]) : null;
    }

}
