<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Logger;

use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Exception\SchemaException;
use Deployer\Host\Host;
use Deployer\Logger\Handler\HandlerInterface;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Logger
{
    private OutputInterface $output;
    private HandlerInterface $fileLog;

    private ?float $startTime = null;

    public function __construct(OutputInterface $output, HandlerInterface $log)
    {
        $this->output = $output;
        $this->fileLog = $log;
    }

    public function command(Host $host, string $type, string $command): void
    {
        if ($this->output->isVerbose()) {
            $this->output->writeln("[{$host->getTag()}] <fg=green;options=bold>$type</> $command");
        }
        $this->fileLog->writeln("[{$host->getAlias()}] $type: $command");
    }

    public function print(Host $host, string $buffer, bool $force = false): void
    {
        if ($this->output->isVerbose() || $force) {
            foreach (explode("\n", rtrim($buffer)) as $line) {
                if (empty($line)) {
                    return;
                }
                $this->output->writeln("[{$host->getTag()}] $line");
            }
        }
        foreach (explode("\n", rtrim($buffer)) as $line) {
            if (empty($line)) {
                return;
            }
            $this->fileLog->writeln("[{$host->getAlias()}] $line");
        }
    }

    public function startTask(Task $task): void
    {
        $this->startTime = round(microtime(true) * 1000);
        if (getenv('GITHUB_WORKFLOW')) {
            $this->output->writeln("::group::task {$task->getName()}");
        } elseif (getenv('GITLAB_CI')) {
            $sectionId = md5($task->getName());
            $start = round($this->startTime / 1000);
            $this->output->writeln("\e[0Ksection_start:{$start}:{$sectionId}\r\e[0K{$task->getName()}");
        } else {
            $this->output->writeln("<fg=cyan;options=bold>task</> {$task->getName()}");
        }
        $this->fileLog->writeln("# task {$task->getName()}");
    }

    public function endTask(Task $task): void
    {
        if (empty($this->startTime)) {
            $this->startTime = round(microtime(true) * 1000);
        }

        $endTime = round(microtime(true) * 1000);
        $millis = $endTime - $this->startTime;
        $seconds = floor($millis / 1000);
        $millis = $millis - $seconds * 1000;
        $taskTime = ($seconds > 0 ? "{$seconds}s " : "") . "{$millis}ms";

        if (getenv('GITHUB_WORKFLOW')) {
            $this->output->writeln("::endgroup::");
        } elseif (getenv('GITLAB_CI')) {
            $sectionId = md5($task->getName());
            $endTime = round($endTime / 1000);
            $this->output->writeln("\e[0Ksection_end:{$endTime}:{$sectionId}\r\e[0K");
        } elseif ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=yellow;options=bold>done</> {$task->getName()} $taskTime");
        }

        $this->fileLog->writeln("# done {$task->getName()} $taskTime");
    }

    public function endOnHost(Host $host): void
    {
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=yellow;options=bold>done</> on {$host->getTag()}");
        }

        $this->fileLog->writeln("# done on {$host->getAlias()}");
    }

    public function renderException(Throwable $exception, Host $host): void
    {
        if ($exception instanceof RunException) {
            $message = "[{$host->getTag()}] <fg=white;bg=red> error </> <comment>in {$exception->getTaskFilename()} on line {$exception->getTaskLineNumber()}:</>\n";
            if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
                $message .= "[{$host->getTag()}] <fg=green;options=bold>run</> {$exception->getCommand()}\n";
                foreach (explode("\n", $exception->getErrorOutput()) as $line) {
                    $line = trim($line);
                    if ($line !== "") {
                        $message .= "[{$host->getTag()}] <fg=red>err</> $line\n";
                    }
                }
                foreach (explode("\n", $exception->getOutput()) as $line) {
                    $line = trim($line);
                    if ($line !== "") {
                        $message .= "[{$host->getTag()}] $line\n";
                    }
                }
            }
            $message .= "[{$host->getTag()}] <fg=red>exit code</> {$exception->getExitCode()} ({$exception->getExitCodeText()})\n";
            $this->output->write($message);
        } else {
            $message = "";
            $class = get_class($exception);
            $file = basename($exception->getFile());
            $line = $exception->getLine();
            if ($exception instanceof Exception) {
                $file = $exception->getTaskFilename();
                $line = $exception->getTaskLineNumber();
            }
            $message .= "[{$host->getTag()}] <fg=white;bg=red> $class </> <comment>in $file on line $line:</>\n";
            $message .= "[{$host->getTag()}]\n";
            foreach (explode("\n", $exception->getMessage()) as $line) {
                $line = trim($line);
                if ($line !== "") {
                    $message .= "[{$host->getTag()}]   <comment>$line</comment>\n";
                }
            }
            $message .= "[{$host->getTag()}]\n";
            if ($this->output->isDebug()) {
                foreach (explode("\n", $exception->getTraceAsString()) as $line) {
                    $line = trim($line);
                    if ($line !== "") {
                        $message .= "[{$host->getTag()}] $line\n";
                    }
                }
            }
            $this->output->write($message);
        }

        $this->fileLog->writeln("[{$host->getAlias()}] $exception");

        if ($exception->getPrevious()) {
            $this->renderException($exception->getPrevious(), $host);
        }
    }
}
