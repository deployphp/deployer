<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class GroupTask extends Task
{
    /**
     * Yes, it is constructor.
     */
    public function __construct()
    {
        // Do nothing
    }

    /**
     * {@inheritdoc
     */
    public function run(Context $context)
    {
        throw new \RuntimeException('Group task should never be running.');
    }
}
