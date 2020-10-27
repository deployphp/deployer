<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Throwable;

class Messenger
{
    private $input;
    private $output;

    /**
     * @var int|double
     */
    private $startTime;

    public function __construct(Input $input, Output $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function startTask(Task $task): void
    {
        $this->startTime = round(microtime(true) * 1000);
        if (!$task->isShallow()) {
            $this->output->writeln("<fg=cyan;options=bold>task</> {$task->getName()}");
        }
    }

    /*
     * Print task was ok.
     */
    public function endTask(Task $task): void
    {
        if ($task->isShallow()) {
            return;
        }
        if (empty($this->startTime)) {
            $this->startTime = round(microtime(true) * 1000);
        }

        $endTime = round(microtime(true) * 1000);
        $millis = $endTime - $this->startTime;
        $seconds = floor($millis / 1000);
        $millis = $millis - $seconds * 1000;
        $taskTime = ($seconds > 0 ? "{$seconds}s " : "") . "{$millis}ms";

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=yellow;options=bold>done</> {$task->getName()} $taskTime");
        }

        if (!empty($this->input->getOption('profile'))) {
            $line = sprintf("%s\t%s\n", $task->getName(), $taskTime);
            file_put_contents($this->input->getOption('profile'), $line, FILE_APPEND);
        }
    }

    public function endOnHost(Host $host): void
    {
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=yellow;options=bold>done</> on {$host->getTag()}");
        }
    }

    public function renderException(Throwable $exception, Host $host): void
    {
        if ($exception instanceof RunException) {

            $message = "";
            $message .= "[{$host->getTag()}] <fg=white;bg=red> error </> <comment>in {$exception->getTaskFilename()} on line {$exception->getTaskLineNumber()}:</>\n";
            if ($this->output->getVerbosity() === Output::VERBOSITY_NORMAL) {
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

        if ($exception->getPrevious()) {
            $this->renderException($exception->getPrevious(), $host);
        }
    }
}
