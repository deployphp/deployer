<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param string $taskName
     */
    public function startTask($taskName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $this->output->write("  ");
            } else {
                $this->output->write("➤ ");
            }

            $this->output->writeln("Executing task $taskName");

            $this->output->setWasWritten(false);
        }
    }

    /**
     * Print task was ok.
     */
    public function endTask()
    {
        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && !$this->output->getWasWritten()) {
            $this->output->write("\033[k\033[1A\r<info>✔</info>\n");
        } else {
            $this->output->writeln("<info>✔</info> Ok");
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
     * @param bool $nonFatal
     */
    public function taskError($nonFatal = true)
    {
        if ($nonFatal) {
            $this->output->writeln("<fg=yellow>✘</fg=yellow> Some errors occurred!");
        } else {
            $this->output->writeln("<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>");
        }
    }

    /**
     * @param string $serverName
     * @param string $exceptionClass
     * @param string $message
     */
    public function taskException($serverName, $exceptionClass, $message)
    {
        $message = "    $message    ";
        $this->output->writeln([
            "",
            "<error>Exception [$exceptionClass] on [$serverName] server</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            "<error>$message</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            ""
        ]);
    }
}
