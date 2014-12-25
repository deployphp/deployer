<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Helper\DeployerHelper;

class SeriesExecutorTest extends \PHPUnit_Framework_TestCase
{
    use DeployerHelper;

    public function testSeriesExecutor()
    {
        list($deployer, $tasks, $servers, $environments, $input, $output) = $this->deployer();

        foreach ($tasks as $task) {
            $task->expects($this->any())->method('runOnServer')->will($this->returnValue(true));
            $tasks['two']->expects($this->any())->method('run');
        }

        $tasks['one']->expects($this->any())->method('isOnce')->will($this->returnValue(true));

        $executor = new SeriesExecutor();
        $executor->run($tasks, $servers, $environments, $input, $output);
    }
}
 