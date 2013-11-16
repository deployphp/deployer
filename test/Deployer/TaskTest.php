<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Tester\ApplicationTester;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $called = false;

        $task = new Task('name', 'desc', function () use (&$called) {
            $called = true;
        });

        $task->run();

        $this->assertTrue($called, 'Task was not called.');
    }

    public function testCreateCommand()
    {
        $task = new Task('name', 'desc', function () {
        });

        $this->assertInstanceOf('Deployer\Tool\Command', $task->createCommand());
    }

    public function testIsPrivate()
    {
        $task = new Task('name', false, function () {
        });
        $this->assertTrue($task->isPrivate());

        $task = new Task('name', 'desc', function () {
        });
        $this->assertFalse($task->isPrivate());
    }
}
