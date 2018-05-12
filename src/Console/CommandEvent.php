<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandEvent
{
    private $command;
    private $input;
    private $output;
    private $exception;
    private $exitCode;

    /**
     * CommandEvent constructor.
     * @param Command $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param null|\Throwable $exception
     * @param int $exitCode
     */
    public function __construct(Command $command, InputInterface $input, OutputInterface $output, $exception = null, $exitCode = 0)
    {
        $this->command = $command;
        $this->input = $input;
        $this->output = $output;
        $this->exception = $exception;
        $this->exitCode = $exitCode;
    }

    /**
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return \Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return mixed
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
}
