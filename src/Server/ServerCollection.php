<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Collection\Collection;

class ServerCollection extends Collection
{
    /**
     * @param string $name
     * @return ServerInterface
     */
    public function get($name)
    {
        return parent::get($name);
    }
}
