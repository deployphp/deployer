<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Helper\DeployerHelper;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;

class TaskCommandTest extends TestCase
{
    use DeployerHelper;

    public function testTaskCommand()
    {
        $this->initialize();

        $this->deployer->tasks['task'] = new Task('task', function () {
        });

        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([
                ['parallel', null],
            ]));

        $executor = $this->createMock('Deployer\Executor\ExecutorInterface');
        $executor->expects($this->once())
            ->method('run');

        $command = new TaskCommand('task', null, $this->deployer);
        $command->executor = $executor;

        $command->run($this->input, $this->output);
    }
}
