<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class Environment
{

    /**
     * Globally defaults values.
     *
     * @var array
     */
    static private $defaults = [];

    /**
     * Array of env values.
     * @var array
     */
    private $values = [];

    /**
     * @param string $name
     * @param int|string|array $value
     */
    public function set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @param int|string|array $default
     * @return int|string|array
     * @throws \RuntimeException
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        } else {
            if (isset(self::$defaults[$name])) {
                if (is_callable(self::$defaults[$name])) {
                    return $this->values[$name] = call_user_func(self::$defaults[$name]);
                } else {
                    return $this->values[$name] = self::$defaults[$name];
                }
            }

            if ($default === null) {
                throw new \RuntimeException("Environment parameter `$name` does not exists.");
            } else {
                return $default;
            }
        }
    }

    /**
     * @return mixed
     */
    public static function getDefault($name)
    {
        return self::$defaults[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public static function setDefault($name, $value)
    {
        self::$defaults[$name] = $value;
    }
}
