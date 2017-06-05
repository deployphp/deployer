<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Console\Application;
use Deployer\Deployer;
use PHPUnit\Framework\TestCase;

class GroupTaskTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testGroupTask()
    {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $task = new GroupTask('group', []);
        $task->run($context);
    }

    public function testOnCondition() {
        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $mock = self::getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();

        //test boolean condition
        $mock
            ->expects(self::once())
            ->method('callback');

        $task = new Task('task', [$mock, 'callback']);
        (new Deployer(new Application()))->tasks->set('task', $task);


        $groupTask = new GroupTask('group', ['task']);
        $groupTask->onCondition(false);
        $task->run($context);

        //and test once
        $groupTask->onCondition(true);
        $task->run($context);
    }
}
