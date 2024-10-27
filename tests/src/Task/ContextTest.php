<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Configuration;
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

        $context = new Context($host);

        $this->assertInstanceOf(Host::class, $context->getHost());
        $this->assertInstanceOf(Configuration::class, $context->getConfig());

        Context::push($context);

        $this->assertEquals($context, Context::get());
        $this->assertEquals($context, Context::pop());
    }
}
