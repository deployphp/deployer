<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

class InformerTest extends TestCase
{
    public function testInformer()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['getVerbosity', 'getWasWritten', 'write', 'isDecorated'])
            ->getMock();

        $output->expects($this->any())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        $informer = new Informer($output);

        $informer->startTask('task');
        $informer->endTask();
    }

    public function testEndTask()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity', 'isDecorated'])
            ->getMock();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringStartsWith('<info>✔</info> Ok'));

        $informer = new Informer($output);
        $informer->endTask();
    }

    public function testTaskError()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
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
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
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
