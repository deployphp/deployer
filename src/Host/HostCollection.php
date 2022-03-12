<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Collection\Collection;

/**
 * @method Host get($name)
 * @method Host[] getIterator()
 */
class HostCollection extends Collection
{
    protected function throwNotFound(string $name): void
    {
        throw new \InvalidArgumentException("Host \"$name\" not found.");
    }
}
