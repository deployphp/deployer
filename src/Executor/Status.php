<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Deployer;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Deployer\Task\Task;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function Deployer\hostTag;

class Status
{
    private $output;

    /**
     * @var int|double
     */
    private $startTime;

    public function __construct(OutputWatcher $output)
    {
        $this->output = $output;
    }

    public function startTask(Task $task)
    {
        $this->startTime = round(microtime(true) * 1000);
        if (!$task->isShallow()) {
            $this->output->writeln("<fg=cyan;options=bold>task</> {$task->getName()}");
            $this->output->setWasWritten(false);
        }
    }

    /*
     * Print task was ok.
     */
    public function endTask(Task $task)
    {
        if ($task->isShallow()) {
            return;
        }

        $endTime = round(microtime(true) * 1000);
        $millis = $endTime - $this->startTime;
        $seconds = floor($millis / 1000);
        $millis = $millis - $seconds * 1000;
        $taskTime = ($seconds > 0 ? "{$seconds}s " : "") . "{$millis}ms";

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("<fg=yellow;options=bold>done</> {$task->getName()} $taskTime");
        }
    }

    public function endOnHost(Host $host)
    {
        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("[{$host->tag()}] <info>ok</info>");
        }
    }

    public function taskException(Throwable $exception, Host $host)
    {
        if ($exception instanceof RunException) {
            $message = "";
            $message .= "[{$host->tag()}] <fg=white;bg=red> error </> <comment>in {$exception->getFilename()} on line {$exception->getLineNumber()}:</comment>\n";
            if ($this->output->getVerbosity() === Output::VERBOSITY_NORMAL) {
                $message .= "[{$host->tag()}] <fg=green;options=bold>run</> {$exception->getCommand()}\n";
                foreach (explode("\n", $exception->getErrorOutput()) as $line) {
                    $line = trim($line);
                    if ($line !== "") {
                        $message .= "[{$host->tag()}] <fg=red>err</> $line\n";
                    }
                }
                foreach (explode("\n", $exception->getOutput()) as $line) {
                    $line = trim($line);
                    if ($line !== "") {
                        $message .= "[{$host->tag()}] $line\n";
                    }
                }
            }
            $message .= "[{$host->tag()}] <fg=red>exit code</> {$exception->getExitCode()} ({$exception->getExitCodeText()})\n";
            $this->output->write($message);
            return;
        }

        $message = "";
        $class = get_class($exception);
        $file = basename($exception->getFile());
        $line = $exception->getLine();
        $message .= "[{$host->tag()}] <fg=white;bg=red> $class </> <comment>in $file on line $line:</comment>\n";
        $message .= "[{$host->tag()}]\n";
        foreach (explode("\n", $exception->getMessage()) as $line) {
            $line = trim($line);
            if ($line !== "") {
                $message .= "[{$host->tag()}]   <comment>$line</comment>\n";
            }
        }
        $message .= "[{$host->tag()}]\n";
        $this->output->write($message);
    }
}
