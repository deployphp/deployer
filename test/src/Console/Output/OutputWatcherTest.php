<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputWatcherTest extends TestCase
{
    public function testOutputWatcher()
    {
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->any())
            ->method('write');

        $output->expects($this->once())
            ->method('setVerbosity');

        $output->expects($this->once())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        $output->expects($this->once())
            ->method('setDecorated');

        $output->expects($this->once())
            ->method('isDecorated');

        $output->expects($this->once())
            ->method('setFormatter');

        $output->expects($this->once())
            ->method('getFormatter');


        $ow = new OutputWatcher($output);

        $ow->write('test');

        $this->assertTrue($ow->getWasWritten());

        $ow->writeln('test');

        $ow->setVerbosity(OutputInterface::VERBOSITY_NORMAL);

        $this->assertEquals(OutputInterface::VERBOSITY_NORMAL, $ow->getVerbosity());

        $ow->setDecorated(true);

        $ow->isDecorated();

        $ow->setFormatter($this->createMock(OutputFormatterInterface::class));

        $ow->getFormatter();

        $ow->setWasWritten(false);

        $this->assertFalse($ow->getWasWritten());
    }
}
