<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Helper\DeployerHelper;

class TaskCommandTest extends \PHPUnit_Framework_TestCase
{
    use DeployerHelper;

    public function testTaskCommand()
    {
        list($deployer, $tasks, $servers, $environments, $input, $output) = $this->deployer();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([
                ['parallel', null],
            ]));

        $executor = $this->getMock('Deployer\Executor\ExecutorInterface');
        $executor->expects($this->once())
            ->method('run')
            ->with($tasks, $servers, $environments, $input, $output);

        $command = new TaskCommand('all', 'desc', $deployer);
        $command->executor = $executor;

        $command->run($input, $output);
    }
}
 