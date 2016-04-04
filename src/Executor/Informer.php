<?php
/* (c) Anton Medvedev <anton@medv.io>
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
     * @var int
     */
    private $startTime;

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
            $this->output->writeln("➤ Executing task <info>$taskName</info>");
            $this->output->setWasWritten(false);
            $this->startTime = round(microtime(true) * 1000);
        }
    }

    /**
     * Print task was ok.
     */
    public function endTask()
    {
        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && !$this->output->getWasWritten()) {
            $this->output->writeln("\r\033[K\033[1A\r<info>✔</info>");
        } else {
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $this->output->writeln("<info>✔</info> Ok");
            } else {
                $endTime = round(microtime(true) * 1000);
                $millis = $endTime - $this->startTime;
                $seconds = floor($millis / 1000);
                $millis = $millis - $seconds * 1000;
                $taskTime = ($seconds > 0 ? "{$seconds}s " : "") . "{$millis}ms";
                $this->output->writeln("<info>✔</info> Ok [$taskTime]");
            }
        }
    }

    /**
     * @param string $serverName
     */
    public function onServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("↳ on [$serverName]");
        }
    }

    /**
     * @param string $serverName
     */
    public function endOnServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("<info>•</info> done on [$serverName]");
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
