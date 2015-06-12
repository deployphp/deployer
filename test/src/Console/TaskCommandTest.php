<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Helper\DeployerHelper;
use Deployer\Task\Scenario\Scenario;
use Deployer\Task\Task;

class TaskCommandTest extends \PHPUnit_Framework_TestCase
{
    use DeployerHelper;

    public function testTaskCommand()
    {
        $this->initialize();

        $this->deployer->tasks['task'] = new Task('task', function () {
        });
        $this->deployer->scenarios['task'] = new Scenario('task');

        $this->input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([
                ['parallel', null],
            ]));

        $executor = $this->getMock('Deployer\Executor\ExecutorInterface');
        $executor->expects($this->once())
            ->method('run');

        $command = new TaskCommand('task', null, $this->deployer);
        $command->executor = $executor;

        $command->run($this->input, $this->output);
    }
}
 