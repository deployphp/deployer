<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testContext()
    {
        $server = $this->getMockBuilder('Deployer\Server\ServerInterface')->disableOriginalConstructor()->getMock();
        $env = $this->getMockBuilder('Deployer\Server\Environment')->disableOriginalConstructor()->getMock();
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->disableOriginalConstructor()->getMock();
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->disableOriginalConstructor()->getMock();

        $context = new Context($server, $env, $input, $output);

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $context->getHost());
        $this->assertInstanceOf('Deployer\Server\Environment', $context->getConfiguration());
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputInterface', $context->getInput());
        $this->assertInstanceOf('Symfony\Component\Console\Output\OutputInterface', $context->getOutput());

        Context::push($context);

        $this->assertEquals($context, Context::get());
        $this->assertEquals($context, Context::pop());
    }
}
