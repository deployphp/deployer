<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\TaskFactory;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testRun()
    {
        $this->expectOutputString('ok');

        $task = new Task(function () {
            echo 'ok';
        });
        $task->run();
    }

    public function testDescription()
    {
        $task = new Task(function () {
        });
        $task->description('desc');

        $this->assertEquals('desc', $task->getDescription());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFactoryInvalidArgumentException()
    {
        TaskFactory::create(null);
    }
}
 