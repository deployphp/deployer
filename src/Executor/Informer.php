<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Deployer;
use Deployer\Log\LogWriter;
use Monolog\Logger;
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

        $deployer = Deployer::get();
        if ($deployer->logs->has("Monolog")){
            $this->logger = Deployer::get()->logs->get("Monolog");
        }
    }

    /**
     * @param string $taskName
     */
    public function startTask($taskName)
    {
        $toWrite = "➤ Executing task <info>$taskName</info>";
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            $this->output->writeln($toWrite);
            $this->output->setWasWritten(false);
            $this->startTime = round(microtime(true) * 1000);
        }
        if ($this->logger) $this->logger->writeLog($toWrite);
    }

    /**
     * Print task was ok.
     */
    public function endTask()
    {
        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && !$this->output->getWasWritten()) {
            $toWrite = "\r\033[K\033[1A\r<info>✔</info>";
            $this->output->writeln($toWrite);
        } else {
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $toWrite = "<info>✔</info> Ok";
                $this->output->writeln($toWrite);
            } else {
                $endTime = round(microtime(true) * 1000);
                $millis = $endTime - $this->startTime;
                $seconds = floor($millis / 1000);
                $millis = $millis - $seconds * 1000;
                $taskTime = ($seconds > 0 ? "{$seconds}s " : "") . "{$millis}ms";
                $toWrite = "<info>✔</info> Ok [$taskTime]";
                $this->output->writeln($toWrite);
            }
        }
        if ($this->logger) $this->logger->writeLog($toWrite);
    }

    /**
     * @param string $serverName
     */
    public function onServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $toWrite = "↳ on [$serverName]";
            $this->output->writeln($toWrite);
            if ($this->logger) $this->logger->writeLog($toWrite);
        }
    }

    /**
     * @param string $serverName
     */
    public function endOnServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $toWrite = "<info>•</info> done on [$serverName]";
            $this->output->writeln($toWrite);
            if ($this->logger) $this->logger->writeLog($toWrite);
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
            $toWrite = "<fg=yellow>✘</fg=yellow> Some errors occurred!";
            $this->output->writeln($toWrite);
            if ($this->logger) $this->logger->writeLog($toWrite,Logger::ERROR);

        } else {
            $toWrite = "<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>";
            $this->output->writeln($toWrite);
            if ($this->logger) $this->logger->writeLog($toWrite,Logger::CRITICAL);
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
        $toWrite = [
            "",
            "<error>Exception [$exceptionClass] on [$serverName] server</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            "<error>$message</error>",
            "<error>" . str_repeat(' ', strlen($message)) . "</error>",
            ""
        ];
        $this->output->writeln($toWrite);
        if ($this->logger) $this->logger->writeLog($toWrite,Logger::EMERGENCY);
    }
}
