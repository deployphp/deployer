<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

use ArrayAccess;
use IteratorAggregate;

interface CollectionInterface extends IteratorAggregate, ArrayAccess
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
