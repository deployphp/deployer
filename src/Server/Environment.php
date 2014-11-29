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
            if ($default === null) {
                throw new \RuntimeException("Environment parameter `$name` does not exists.");
            } else {
                return $default;
            }
        }
    }
}
