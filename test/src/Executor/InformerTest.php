<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;


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

        // TODO: Check something.
        $informer->startTask('task');
        $informer->onServer('server');
        $informer->endTask();
    }
}
 