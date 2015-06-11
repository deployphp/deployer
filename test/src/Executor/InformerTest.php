<?php

/**
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Task\NonFatalException;
use Symfony\Component\Console\Output\OutputInterface;

class InformerTest extends \PHPUnit_Framework_TestCase
{
    public function testInformer()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['getVerbosity', 'getWasWritten', 'write'])
            ->getMock();

        $output->expects($this->any())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        $informer = new Informer($output);

        $taskId = uniqid();

        // TODO: Check something.
        $informer->startTask('task', $taskId);
        $informer->onServer('server');
        $informer->endTask($taskId);
    }

    public function testEndTask()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity'])
            ->getMock();

        $taskId = uniqid();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo("<info>✔</info> Ok <fg=black>#$taskId</fg=black>"));

        $informer = new Informer($output);
        $informer->endTask($taskId);
    }

    public function testTaskError()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity'])
            ->getMock();

        $taskId = uniqid();

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>'));

        $informer = new Informer($output);
        $informer->taskError($taskId, false);
    }

    public function testTaskErrorNonFatat()
    {
        $output = $this->getMockBuilder('Deployer\Console\Output\OutputWatcher')
            ->disableOriginalConstructor()
            ->setMethods(['writeln', 'getVerbosity'])
            ->getMock();

        $taskId = uniqid();

        $output->expects($this->once()) 
            ->method('writeln')
            ->with($this->equalTo("<fg=yellow>✘</fg=yellow> Some errors occurred! <fg=black>#$taskId</fg=black>"));

        $informer = new Informer($output);
        $informer->taskError($taskId, true);
    }
}

