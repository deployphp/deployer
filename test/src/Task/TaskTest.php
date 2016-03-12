<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testTask()
    {
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->exactly(1))
            ->method('callback');

        $task = new Task('task_name', function () use ($mock) {
            $mock->callback();
        });
        
        $context = $this->getMockBuilder('Deployer\Task\Context')->disableOriginalConstructor()->getMock();

        $task->run($context);

        $this->assertEquals('task_name', $task->getName());

        $task->desc('Task description.');
        $this->assertEquals('Task description.', $task->getDescription());

        $task->once();
        $this->assertTrue($task->isOnce());

        $task->onlyOn(['server']);
        $this->assertEquals(['server' => 0], $task->getOnlyOn());
        $this->assertTrue($task->runOnServer('server'));

        $task->onlyOn([]);
        $this->assertTrue($task->runOnServer('server'));

        $task->onlyOn('server');
        $this->assertEquals(['server' => 0], $task->getOnlyOn());
        $this->assertTrue($task->runOnServer('server'));

        $task->onlyOn();
        $this->assertTrue($task->runOnServer('server'));

        $task->setPrivate();
        $this->assertTrue($task->isPrivate());

        $task->onlyForStage(['staging', 'production']);
        $this->assertEquals(['staging' => 0, 'production' => 1], $task->getOnlyForStage());
        $this->assertTrue($task->runForStages(['staging']));
        $this->assertTrue($task->runForStages(['production']));
        $this->assertTrue($task->runForStages(['staging', 'production']));

        $task->onlyForStage('staging', 'production');
        $this->assertEquals(['staging' => 0, 'production' => 1], $task->getOnlyForStage());
        $this->assertTrue($task->runForStages(['staging']));
        $this->assertTrue($task->runForStages(['production']));
        $this->assertTrue($task->runForStages(['staging', 'production']));

        $task->onlyForStage();
        $this->assertTrue($task->runForStages('anything'));
    }
}
