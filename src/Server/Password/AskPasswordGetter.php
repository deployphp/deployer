<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

use Deployer\Task\Context;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Ask password getter
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AskPasswordGetter implements PasswordGetterInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Construct
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword($host, $user)
    {
        $askMessage = sprintf('[%s@%s] Password:', $user, $host);

        $questionHelper = $this->createQuestionHelper();
        $question = new Question($askMessage);
        $question->setHidden(true);

        return $questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * Create a lazy ask password getter with use context output and input interfaces
     *
     * @return CallablePasswordGetter
     */
    public static function createLazyGetter()
    {
        return new CallablePasswordGetter(function ($host, $user) {
            $context = Context::get();
            $output = $context->getOutput();
            $input = $context->getInput();

            $askPasswordGetter = new AskPasswordGetter($input, $output);

            return $askPasswordGetter->getPassword($host, $user);
        });
    }

    /**
     * Create question helper
     *
     * @return QuestionHelper
     */
    protected function createQuestionHelper()
    {
        return new QuestionHelper();
    }
}
