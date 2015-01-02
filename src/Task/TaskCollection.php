<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Collection\Collection;

class TaskCollection extends Collection
{
    /**
     * @param string $name
     * @return Task
     */
    public function get($name)
    {
        return parent::get($name);
    }
}
