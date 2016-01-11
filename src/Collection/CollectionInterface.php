<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess
{
    /**
     * @param string $name
     * @return mixed
     */
    public function get($name);

    /**
     * @param string $name
     * @param mixed $object
     */
    public function set($name, $object);

    /**
     * @param string $name
     * @return mixed
     */
    public function has($name);
}
