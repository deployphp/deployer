<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use PHPUnit\Framework\TestCase;

class GroupTaskTest extends TestCase
{
    public function testGroupTask()
    {
        $this->expectException(\RuntimeException::class);

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $task = new GroupTask('group', []);
        $task->run($context);
    }
}
