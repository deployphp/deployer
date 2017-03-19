<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Application;
use Deployer\Console\Output\Informer;
use Deployer\Console\Output\VerbosityString;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ParallelExecutor implements ExecutorInterface
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
     * @var Informer
     */
    private $informer;

    /**
     * @var Application
     */
    private $console;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Informer $informer
     * @param Application $console
     */
    public function __construct(InputInterface $input, OutputInterface $output, Informer $informer, Application $console)
    {
        $this->input = $input;
        $this->output = $output;
        $this->informer = $informer;
        $this->console = $console;
    }

    /**
     * {@inheritdoc}
     */
    public function run($tasks, $hosts)
    {
        $localhost = new Localhost();
        $limit = (int)$this->input->getOption('limit') ?: count($hosts);

        foreach ($tasks as $task) {
            $success = true;
            $this->informer->startTask($task->getName());

            if ($task->isLocal()) {
                $task->run(new Context($localhost, $this->input, $this->output));
            } else {
                foreach (array_chunk($hosts, $limit) as $chunk) {
                    $this->runTask($chunk, $task);
                }
            }

            if ($success) {
                $this->informer->endTask();
            } else {
                $this->informer->taskError();
            }
        }
    }

    /**
     * Run task on hosts.
     *
     * @param array $hosts
     * @param Task $task
     * @return int
     */
    private function runTask(array $hosts, Task $task)
    {
        $processes = [];

        foreach ($hosts as $hostname => $host) {
            $processes[$hostname] = $this->getProcess($host, $task);
        }

        $callback = function ($type, $host, $output) {
            $this->output->write($output);
        };

        $this->startProcesses($processes);

        while ($this->areRunning($processes)) {
            $this->gatherOutput($processes, $callback);
        }
        $this->gatherOutput($processes, $callback);

        return $this->gatherExitCodes($processes);
    }

    /**
     * Get process for task on host.
     *
     * @param Host|Localhost $host
     * @param Task $task
     * @return Process
     */
    protected function getProcess($host, Task $task)
    {
        $dep = getenv('_');
        $options = $this->generateOptions();
        $hostname = $host->getHostname();
        $taskName = $task->getName();

        if ($this->output->isDecorated()) {
            $options .= ' --ansi';
        }

        $process = new Process("$dep worker $options --hostname $hostname --task $taskName");
        $process->setTty(true);
        return $process;
    }

    /**
     * Start all of the processes.
     *
     * @param Process[] $processes
     * @return void
     */
    protected function startProcesses(array $processes)
    {
        foreach ($processes as $process) {
            $process->start();
        }
    }

    /**
     * Determine if any of the processes are running.
     *
     * @param Process[] $processes
     * @return bool
     */
    protected function areRunning(array $processes)
    {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gather the output from all of the processes.
     *
     * @param Process[] $processes
     * @param callable $callback
     */
    protected function gatherOutput(array $processes, callable $callback)
    {
        foreach ($processes as $host => $process) {
            $methods = [
                Process::OUT => 'getIncrementalOutput',
                Process::ERR => 'getIncrementalErrorOutput',
            ];
            foreach ($methods as $type => $method) {
                $output = $process->{$method}();
                if (!empty($output)) {
                    $callback($type, $host, $output);
                }
            }
        }
    }

    /**
     * Gather the cumulative exit code for the processes.
     *
     * @param Process[] $processes
     * @return int
     */
    protected function gatherExitCodes(array $processes)
    {
        $code = 0;
        foreach ($processes as $process) {
            $code = $code + $process->getExitCode();
        }
        return $code;
    }

    /**
     * Generate options and arguments string.
     * @return string
     */
    private function generateOptions()
    {
        $input = '';

        $verbosity = new VerbosityString($this->output);
        $input .= $verbosity;

        // Deploy file
        $value = $this->input->getOption('file');
        if ($value) {
            $input .= " --file $value";
        }

        // Console options
        foreach (['quiet', 'ansi', 'no-ansi', 'no-interaction'] as $option) {
            $value = $this->input->getOption($option);
            if ($value) {
                $input .= " --$option";
            }
        }

        // Get user arguments
        foreach ($this->console->getUserDefinition()->getArguments() as $argument) {
            $value = $this->input->getArgument($argument->getName());
            if ($value) {
                $input .= " $value";
            }
        }

        // Get user options
        foreach ($this->console->getUserDefinition()->getOptions() as $option) {
            $value = $this->input->getOption($option->getName());
            if ($value) {
                $input .= " --{$option->getName()} $value";
            }
        }

        return $input;
    }
}
