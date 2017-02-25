<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Collection\Collection;

/**
 * Overriding DotArray Access Implementation of Collection
 * ServerCollection stores servers with dot in the name in a
 * simple key
 *
 */
class ServerCollection extends Collection
{
    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->collection[$name];
        } else {
            $class = explode('\\', static::class);
            $class = end($class);
            throw new \RuntimeException("Object `$name` does not exist in $class.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return array_key_exists($name, $this->collection);
    }
}
