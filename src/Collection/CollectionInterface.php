<?php declare(strict_types=1);
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
    public function get(string $name);

    /**
     * @param string $name
     * @param mixed $object
     * @return void
     */
    public function set(string $name, $object);

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
}
