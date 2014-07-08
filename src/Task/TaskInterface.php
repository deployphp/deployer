<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

interface TaskInterface 
{
    /**
     * Return task body.
     * @return Runner[]
     */
    public function get();
} 