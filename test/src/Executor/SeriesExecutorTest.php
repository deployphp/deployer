<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Helper\DeployerHelper;
use Deployer\Server\Environment;
use Deployer\Server\Local;
use Deployer\Task\Task;

class SeriesExecutorTest extends \PHPUnit_Framework_TestCase
{
    use DeployerHelper;

    public function testSeriesExecutor()
    {
        $this->initialize();

        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['task', 'once', 'only'])
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('task');
        $mock->expects($this->once())
            ->method('once');
        $mock->expects($this->once())
            ->method('only');

        $task = new Task('task', function () use($mock) {
            $mock->task();
        });

        $taskOne = new Task('once', function () use($mock) {
            $mock->once();
        });
        $taskOne->once();

        $taskOnly = new Task('only', function () use($mock) {
            $mock->only();
        });
        $taskOnly->onlyOn(['one']);

        $tasks = [$task, $taskOne, $taskOnly];

        $environments = [
            'one' => new Environment(),
            'two' => new Environment(),
        ];
        $servers = [
            'one' => new Local(),
            'two' => new Local(),
        ];

        $executor = new SeriesExecutor();
        $executor->run($tasks, $servers, $environments, $this->input, $this->output);
    }
}
 