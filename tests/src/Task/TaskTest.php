<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use PHPUnit\Framework\TestCase;
use function Deployer\invoke;
use function Deployer\task;

class TaskTest extends TestCase
{
    protected function tearDown(): void
    {
        StubTask::$runned = 0;
    }

    public function testTask()
    {
        $mock = self::getMockBuilder('stdClass')
            ->addMethods(['callback'])
            ->getMock();
        $mock
            ->expects(self::exactly(1))
            ->method('callback');

        $task = new Task('task_name', function () use ($mock) {
            $mock->callback();
        });

        $context = self::getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $task->run($context);

        self::assertEquals('task_name', $task->getName());

        $task->desc('Task description.');
        self::assertEquals('Task description.', $task->getDescription());

        $task->hidden();
        self::assertTrue($task->isHidden());

        $task->once();
        self::assertTrue($task->isOnce());

        $task->oncePerNode();
        self::assertTrue($task->isOncePerNode());
    }

    public function testInit()
    {
        $context = self::getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        // Test create task with [$object, 'method']
        $mock1 = self::getMockBuilder('stdClass')
            ->addMethods(['callback'])
            ->getMock();
        $mock1
            ->expects(self::once())
            ->method('callback');
        $task1 = new Task('task1', [$mock1, 'callback']);
        $task1->run($context);

        // Test create task with anonymous functions
        $mock2 = self::getMockBuilder('stdClass')
            ->addMethods(['callback'])
            ->getMock();
        $mock2
            ->expects(self::once())
            ->method('callback');
        $task2 = new Task('task2', function () use ($mock2) {
            $mock2->callback();
        });
        $task2->run($context);

        self::assertEquals(0, StubTask::$runned);
        $task3 = new Task('task3', new StubTask());
        $task3->run($context);
        self::assertEquals(1, StubTask::$runned);
    }

    public function testGroupInvoke(): void
    {
        $spy = new StubTask();

        task('foo', $spy);
        task('bar', $spy);
        task('group', ['foo', 'bar']);

        (new Task('group:invoke', function () {
            invoke('group');
        }))->run(new Context(new Host('localhost')));

        $this->assertSame(2, StubTask::$runned);
    }
}

/**
 * Stub class for task callable by __invoke()
 */
class StubTask
{
    public static $runned = 0;

    public function __invoke()
    {
        self::$runned++;
    }
}
