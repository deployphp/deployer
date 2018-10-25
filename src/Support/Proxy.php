<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

class Proxy
{
    /**
     * @var object[]
     */
    private $objects;

    /**
     * @param object[] $objects
     */
    public function __construct(array $objects)
    {
        $this->objects = $objects;
    }

    /**
     * @param mixed[] $arguments
     *
     * @return static
     */
    public function __call(string $name, $arguments): self
    {
        foreach ($this->objects as $object) {
            $object->$name(...$arguments);
        }
        return $this;
    }
}
