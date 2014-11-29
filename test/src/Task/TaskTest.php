<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testTask()
    {
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->exactly(1))
            ->method('callback');

        $task = new Task(function () use ($mock) {
            $mock->callback();
        });

        $task->run();

        $task->desc('Task description.');
        $this->assertEquals('Task description.', $task->getDescription());
    }
}
