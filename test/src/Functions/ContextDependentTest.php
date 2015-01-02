<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\Context;
use Symfony\Component\Console\Application;

class ContextDependentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Application
     */
    private $console;

    protected function setUp()
    {
        $this->console = new Application();
        
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $server = $this->getMockBuilder('Deployer\Server\ServerInterface')->disableOriginalConstructor()->getMock();
        $env = $this->getMockBuilder('Deployer\Server\Environment')->disableOriginalConstructor()->getMock();
        
        $this->deployer = new Deployer($this->console, $input, $output);
        
        Context::push(new Context($server, $env, $input, $output));
    }

    protected function tearDown()
    {
        unset($this->deployer);
        
        $this->deployer = null;
        
        Context::pop();
    }

    public function testWrite()
    {
        // So what to test here? =)
        write('Hello world!');
        writeln('Hello world!');
    }
    
    public function testAsk()
    {
        $answer = ask('Question?', 'default');
        $this->assertEquals('default', $answer);


        $helper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $helper->expects($this->once())
            ->method('ask')
            ->will($this->returnValue('Anton'));
        
        $helperSet = $this->getMock('Symfony\Component\Console\Helper\HelperSet');
        $helperSet->expects($this->once())
            ->method('get')
            ->with('question')
            ->will($this->returnValue($helper));
        
        $this->console->setHelperSet($helperSet);
        
        $answer = ask('What is your name?');

        $this->assertEquals('Anton', $answer);
    }

    public function testAskConfirmation()
    {
        $answer = askConfirmation('Do it?');
        $this->assertFalse($answer);


        $helper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $helper->expects($this->once())
            ->method('ask')
            ->will($this->returnValue(true));

        $helperSet = $this->getMock('Symfony\Component\Console\Helper\HelperSet');
        $helperSet->expects($this->once())
            ->method('get')
            ->with('question')
            ->will($this->returnValue($helper));

        $this->console->setHelperSet($helperSet);

        $answer = askConfirmation('Do it?');

        $this->assertEquals(true, $answer);
    }

    public function testAskHiddenResponse()
    {
        $password = askHiddenResponse('Password?');
        $this->assertEquals(null, $password);


        $helper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $helper->expects($this->once())
            ->method('ask')
            ->will($this->returnValue('pass'));

        $helperSet = $this->getMock('Symfony\Component\Console\Helper\HelperSet');
        $helperSet->expects($this->once())
            ->method('get')
            ->with('question')
            ->will($this->returnValue($helper));

        $this->console->setHelperSet($helperSet);

        $answer = askHiddenResponse('Password?');

        $this->assertEquals('pass', $answer);
    }
}
