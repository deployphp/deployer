<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Configuration\Configuration;
use Deployer\Host\Host;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContextTest extends TestCase
{
    public function testContext()
    {
        $host = $this->getMockBuilder(Host::class)->disableOriginalConstructor()->getMock();
        $host
            ->expects($this->once())
            ->method('config')
            ->willReturn($this->createMock(Configuration::class));

        $input = $this->getMockBuilder(InputInterface::class)->disableOriginalConstructor()->getMock();
        $output = $this->getMockBuilder(OutputInterface::class)->disableOriginalConstructor()->getMock();

        $context = new Context($host, $input, $output);

        $this->assertInstanceOf(Host::class, $context->getHost());
        $this->assertInstanceOf(Configuration::class, $context->getConfig());
        $this->assertInstanceOf(InputInterface::class, $context->getInput());
        $this->assertInstanceOf(OutputInterface::class, $context->getOutput());

        Context::push($context);

        $this->assertEquals($context, Context::get());
        $this->assertEquals($context, Context::pop());
    }
}
