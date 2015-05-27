<?php

/**
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

use Deployer\Task\Context;
use Symfony\Component\Console\Question\Question;

/**
 * Testing ask password getter
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AskPasswordGetterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var AskPasswordGetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $askPasswordGetter;

    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $questionHelper;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->input = $this->getMockForAbstractClass('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMockForAbstractClass('Symfony\Component\Console\Output\OutputInterface');
        $this->questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');

        $this->askPasswordGetter = $this->getMock(
            'Deployer\Server\Password\AskPasswordGetter',
            [ 'createQuestionHelper' ],
            [ $this->input, $this->output ]
        );
    }

    /**
     * Test create question helper (use construct)
     */
    public function testCreateWithConstruct()
    {
        new AskPasswordGetter($this->input, $this->output);
    }

    /**
     * Test get password
     */
    public function testGetPassword()
    {
        $this->askPasswordGetter->expects($this->once())->method('createQuestionHelper')
            ->will($this->returnValue($this->questionHelper));

        $this->questionHelper->expects($this->once())->method('ask')
            ->with($this->input, $this->output, $this->isInstanceOf('Symfony\Component\Console\Question\Question'))
            ->will($this->returnCallback(function ($input, $output, Question $question) {
                // Check question
                $this->assertTrue($question->isHidden(), 'The question must be hidden');
                $this->assertEquals('[user@host] Password:', $question->getQuestion());

                // Return password
                return 'some_password';
            }));

        $realPassword = $this->askPasswordGetter->getPassword('host', 'user');

        $this->assertEquals('some_password', $realPassword, 'Password not mismatch.');
    }

    /**
     * Test create lazy ask password getter
     */
    public function testCreateLazyAskPasswordGetter()
    {
        $lazyGetter = AskPasswordGetter::createLazyGetter();

        $this->assertInstanceOf('Deployer\Server\Password\CallablePasswordGetter', $lazyGetter);

        $context = $this->getMock(
            'Deployer\Task\Context',
            [ 'getInput', 'getOutput' ],
            [],
            '',
            false
        );

        $context->expects($this->any())->method('getInput')
            ->will($this->returnValue($this->input));

        $context->expects($this->any())->method('getOutput')
            ->will($this->returnValue($this->output));

        // Push own context
        Context::push($context);

        $lazyGetter->getPassword('host', 'user');

        // Pop own context
        Context::pop();
    }

    /**
     * Test create question helper.
     * Attention: method is protected, then use Reflection for access to this method
     */
    public function testCreateQuestionHelper()
    {
        $askPasswordGetter = new AskPasswordGetter($this->input, $this->output);
        $ref = new \ReflectionObject($askPasswordGetter);
        $method = $ref->getMethod('createQuestionHelper');
        $method->setAccessible(true);

        $questionHelper = $method->invoke($askPasswordGetter);

        $this->assertInstanceOf('Symfony\Component\Console\Helper\QuestionHelper', $questionHelper);
    }
}
