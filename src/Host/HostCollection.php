<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Collection\Collection;

/**
 * @method Host get($name)
 */
class HostCollection extends Collection
{
    protected function throwNotFound(string $name)
    {
        throw new \InvalidArgumentException("Host `$name` not found");
    }
}
