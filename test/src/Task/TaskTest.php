<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testTask()
    {
        $mock = self::getMockBuilder('stdClass')
            ->setMethods(['callback'])
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

        $task->local();
        self::assertTrue($task->isLocal());

        $task->setPrivate();
        self::assertTrue($task->isPrivate());

        $task->once();
        self::assertTrue($task->isOnce());
    }

    public function testShouldBePerformed()
    {
        $a = (new Host('a'))->stage('prod')->roles('app');
        $b = (new Host('b'))->stage('prod')->roles('db');
        $c = (new Host('c'))->stage('beta')->roles('app', 'db');

        $task = new Task('task');
        $task
            ->onStage('prod')
            ->onRoles('app');
        self::assertEquals([true, false, false], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onStage('prod')
            ->onRoles('db');
        self::assertEquals([false, true, false], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onStage('beta')
            ->onRoles('app', 'db');
        self::assertEquals([false, false, true], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onStage('beta');
        self::assertEquals([false, false, true], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onRoles('db');
        self::assertEquals([false, true, true], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onRoles('app');
        self::assertEquals([true, false, true], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onHosts('a', 'b');
        self::assertEquals([true, true, false], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        $task = new Task('task');
        $task
            ->onRoles('app')
            ->onHosts('a', 'b');
        self::assertEquals([true, false, false], array_map([$task, 'shouldBePerformed'], [$a, $b, $c]));

        self::assertTrue($task->shouldBePerformed());
    }

    public function testInit()
    {
        $context = self::getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        // Test create task with [$object, 'method']
        $mock1 = self::getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();
        $mock1
            ->expects(self::once())
            ->method('callback');
        $task1 = new Task('task1', [$mock1, 'callback']);
        $task1->run($context);

        // Test create task with anonymous functions
        $mock2 = self::getMockBuilder('stdClass')
            ->setMethods(['callback'])
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

    public function testOnce()
    {
        $a = (new Host('a'))->stage('prod')->roles('app');
        $b = (new Host('b'))->stage('prod')->roles('app');

        $context = self::getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        // Test create task with [$object, 'method']
        $mock = self::getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();

        $task1 = new Task('only:once', [$mock, 'callback']);
        $task1
            ->onHosts('a', 'b')
            ->once();
        self::assertTrue($task1->shouldBePerformed($a));
        self::assertEquals([true, true], array_map([$task1, 'shouldBePerformed'], [$a, $b]));
        $task1->run($context);
        self::assertFalse($task1->shouldBePerformed($b));
        self::assertEquals([false, false], array_map([$task1, 'shouldBePerformed'], [$a, $b]));

        $task2 = new Task('multiple:runs', [$mock, 'callback']);
        $task2
            ->onHosts('a', 'b');
        self::assertTrue($task2->shouldBePerformed($a));
        self::assertEquals([true, true], array_map([$task2, 'shouldBePerformed'], [$a, $b]));
        $task2->run($context);
        self::assertTrue($task2->shouldBePerformed($b));
        self::assertEquals([true, true], array_map([$task2, 'shouldBePerformed'], [$a, $b]));
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
