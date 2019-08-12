<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Executor\Status;
use Deployer\Console\Output\OutputWatcher;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class StatusTest extends TestCase
{
    public function testInformer()
    {
        $output = $this->getMockBuilder(OutputWatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVerbosity', 'getWasWritten', 'write', 'isDecorated'])
            ->getMock();

        $output->expects($this->atLeastOnce())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        $informer = new Status($output);
        $task = new Task('task');

        $informer->startTask($task);
        $informer->endTask($task);
    }

    public function testTaskError()
    {
        $output = $this->getMockBuilder(OutputWatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity', 'isDecorated'])
            ->getMock();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>'));

        $informer = new Status($output);
        $informer->taskError(false);
    }

    public function testTaskErrorNonFatal()
    {
        $output = $this->getMockBuilder(OutputWatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity'])
            ->getMock();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('<fg=yellow>✘</fg=yellow> Some errors occurred!'));

        $informer = new Status($output);
        $informer->taskError(true);
    }
}
