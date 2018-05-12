<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\Informer;
use Deployer\Console\Output\OutputWatcher;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class InformerTest extends TestCase
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

        $informer = new Informer($output);
        $task = new Task('task');

        $informer->startTask($task);
        $informer->endTask($task);
    }

    public function testEndTask()
    {
        $output = $this->getMockBuilder(OutputWatcher::class)
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity', 'isDecorated'])
            ->getMock();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringStartsWith('<info>✔</info> Ok'));

        $informer = new Informer($output);
        $task = new Task('task');

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

        $informer = new Informer($output);
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

        $informer = new Informer($output);
        $informer->taskError(true);
    }
}
