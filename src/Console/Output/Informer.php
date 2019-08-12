<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console\Output;

use Deployer\Deployer;
use Deployer\Host\Host;
use function Deployer\hostnameTag;
use Deployer\Task\Task;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;

class Informer
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
            $this->output->writeln("<info>task</info> {$task->getName()}");
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

        if ($this->output->isVerbose()) {
            $this->output->writeln("<info>done</info> $taskTime");
        }
    }

    public function endOnHost(string $hostname)
    {
        if ($this->output->isVerbose()) {
            $this->output->writeln("<info>done</info> $hostname");
        }
    }

    public function taskError(bool $nonFatal = true)
    {
        if ($nonFatal) {
            $this->output->writeln("<fg=yellow>✘</fg=yellow> Some errors occurred!");
        } else {
            $this->output->writeln("<fg=red>✘</fg=red> <options=underscore>Some errors occurred!</options=underscore>");
        }
    }

    /**
     * @param \Throwable $exception
     * @param Host $host
     */
    public function taskException($exception, $host = null)
    {
        /** @var FormatterHelper $formatter */
        $formatter = Deployer::get()->getHelper('formatter');
        $messages = array_filter(array_map('trim', explode("\n", $exception->getMessage())), function ($line) {
            return !empty($line);
        });
        $exceptionClass = get_class($exception);
        array_unshift($messages, "[$exceptionClass]");

        $prefix = '';
        if (!empty($host)) {
            $prefix = hostnameTag($host->getHostname());
        }

        $file = basename($exception->getFile());
        $line = $exception->getLine();
        $comment = "$prefix<comment>In $file on line $line:</comment>\n";
        $this->output->writeln($comment . $formatter->formatBlock($messages, 'error', true) . "\n\n");

        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->writeln('<comment>Exception trace:</comment>', OutputInterface::VERBOSITY_QUIET);

            // exception related properties
            $trace = $exception->getTrace();
            array_unshift($trace, [
                'function' => '',
                'file' => $exception->getFile() !== null ? $exception->getFile() : 'n/a',
                'line' => $exception->getLine() !== null ? $exception->getLine() : 'n/a',
                'args' => [],
            ]);

            for ($i = 0, $count = count($trace); $i < $count; ++$i) {
                $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
                $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
                $function = $trace[$i]['function'];
                $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                $this->output->writeln(sprintf(' %s%s%s() at <info>%s:%s</info>', $class, $type, $function, $file, $line), OutputInterface::VERBOSITY_QUIET);
            }

            $this->output->writeln('', OutputInterface::VERBOSITY_QUIET);
        }
    }
}
