<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Helper\DeployerHelper;
use AspectMock\Test as test;

class TaskCommandTest extends \PHPUnit_Framework_TestCase
{
    use DeployerHelper;
    
    public function testTaskCommand()
    {
        list($deployer, $tasks, $servers, $input, $output) = $this->deployer();

        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([
                ['server', null],
                ['parallel', null],
            ]));

        $command = new TaskCommand('all', 'desc', $deployer);

        $executor = test::double('Deployer\Executor\SeriesExecutor', ['run' => null]);

        $command->run($input, $output);

        $executor->verifyInvokedOnce('run', [$tasks, $servers, $input, $output]);
    }

    protected function tearDown()
    {
        test::clean();
    }
}
 