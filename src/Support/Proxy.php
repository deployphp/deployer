<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

class Proxy
{
    private $objects;

    public function __construct(array $objects)
    {
        $this->objects = $objects;
    }

    public function __call($name, $arguments)
    {
        foreach ($this->objects as $object) {
            $object->$name(...$arguments);
        }
        return $this;
    }
}
