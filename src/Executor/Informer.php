<?php

/*
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Informer system.
 * This informer prints status of execute tasks to Output.
 */
class Informer
{
    /**
     * @var \Deployer\Console\Output\OutputWatcher
     */
    private $output;

    /**
     * @param \Deployer\Console\Output\OutputWatcher $output
     */
    public function __construct(OutputWatcher $output)
    {
        $this->output = $output;
    }

    /**
     * Start run task
     *
     * @param string $taskName  Name of task
     * @param string $taskId    The unique identifier of task for control with many task in group
     */
    public function startTask($taskName, $taskId)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $this->output->write("  ");
            } else {
                $this->output->write("➤ ");
            }

            $this->output->writeln("Executing task $taskName <fg=black>#$taskId</fg=black>");

            $this->output->setWasWritten(false);
        }
    }

    /**
     * Print task was ok.
     *
     * @param string $taskId
     */
    public function endTask($taskId)
    {
        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && !$this->output->getWasWritten()) {
            $this->output->write("\033[k\033[1A\r<info>✔</info>\n");
        } else {
            $this->output->writeln("<info>✔</info> Ok <fg=black>#$taskId</fg=black>");
        }
    }

    /**
     * @param string $serverName
     */
    public function onServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("⤷ on [$serverName]");
        }
    }

    /**
     * @param string $serverName
     */
    public function endOnServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("<info>⤶</info> done on [$serverName]");
        }
    }

    /**
     * Print error.
     *
     * @param string $taskId
     * @param bool   $nonFatal
     */
    public function taskError($taskId, $nonFatal = true)
    {
        if ($nonFatal) {
            $this->output->writeln("<fg=yellow>✘</fg=yellow> Some errors occurred! <fg=black>#$taskId</fg=black>");
        } else {
            $this->output->writeln("<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>");
        }
    }

    /**
     * Task exception
     *
     * @param string $serverName
     * @param string $taskName
     * @param string $taskId
     * @param string $exceptionClass
     * @param string $message
     */
    public function taskException($serverName, $taskName, $taskId, $exceptionClass, $message)
    {
        $message = "    $message    ";
        $this->output->writeln([
            "",
            "<error>Exception [$exceptionClass] on [$serverName] server for task [$taskName:$taskId]</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            "<error>$message</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            ""
        ]);
    }
}
