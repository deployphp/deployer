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
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Storage;
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

        // We need contexts here for usage inside `on` function. Pass input/output to callback of it.
        // This allows to use code like this in parallel mode:
        //
        //     host('prod')
        //         ->set('branch', function () {
        //             return input()->getOption('branch') ?: 'production';
        //     })
        //
        // Otherwise `input()` wont be accessible (i.e. null)
        //
        Context::push(new Context($localhost, $this->input, $this->output));
        {
            Storage::persist($hosts);
        }
        Context::pop();

        foreach ($tasks as $task) {
            $success = true;
            $this->informer->startTask($task);

            if ($task->isLocal()) {
                Storage::load($hosts);
                {
                    $task->run(new Context($localhost, $this->input, $this->output));
                }
                Storage::flush($hosts);
            } else {
                foreach (array_chunk($hosts, $limit) as $chunk) {
                    $exitCode = $this->runTask($chunk, $task);

                    switch ($exitCode) {
                        case 1:
                            throw new GracefulShutdownException();
                        case 2:
                            $success = false;
                            break;
                        case 255:
                            throw new Exception();
                    }
                }
            }

            if ($success) {
                $this->informer->endTask($task);
            } else {
                $this->informer->taskError();
            }
        }
    }

    /**
     * Run task on hosts.
     *
     * @param Host[] $hosts
     * @param Task $task
     * @return int
     */
    private function runTask(array $hosts, Task $task)
    {
        $processes = [];

        foreach ($hosts as $host) {
            if ($task->shouldBePerformed($host)) {
                $processes[$host->getHostname()] = $this->getProcess($host, $task);
                if ($task->isOnce()) {
                    break;
                }
            }
        }

        $callback = function ($type, $host, $output) {
            $output = rtrim($output);
            if (!empty($output)) {
                $this->output->writeln($output);
            }
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
     * @param Host $host
     * @param Task $task
     * @return Process
     */
    protected function getProcess($host, Task $task)
    {
        $dep = PHP_BINARY . ' ' . DEPLOYER_BIN;
        $options = $this->generateOptions();
        $arguments = $this->generateArguments();
        $hostname = $host->getHostname();
        $taskName = $task->getName();
        $configFile = $host->get('host_config_storage');
        $value = $this->input->getOption('file');
        $file = $value ? "--file='$value'" : '';

        if ($this->output->isDecorated()) {
            $options .= ' --ansi';
        }

        $command = "$dep $file worker $arguments $options --hostname $hostname --task $taskName --config-file $configFile";
        $process = new Process($command);

        if (!defined('DEPLOYER_PARALLEL_PTY')) {
            $process->setPty(true);
        }

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
            if ($process->getExitCode() > 0) {
                $code = $process->getExitCode();
            }
        }
        return $code;
    }

    /**
     * Generate options and arguments string.
     * @return string
     */
    private function generateOptions()
    {
        $verbosity = new VerbosityString($this->output);
        $input = $verbosity;

        // Get user arguments
        foreach ($this->console->getUserDefinition()->getArguments() as $argument) {
            $value = $this->input->getArgument($argument->getName());
            if ($value) {
                $input .= " $value";
            }
        }

        // Get user options
        foreach ($this->console->getUserDefinition()->getOptions() as $option) {
            $name = $option->getName();
            $value = $this->input->getOption($name);

            if ($value) {
                $input .= " --{$name}";

                if ($option->acceptValue()) {
                    $input .= " {$value}";
                }
            }
        }

        return $input;
    }

    private function generateArguments(): string
    {
        $arguments = '';

        if ($this->input->hasArgument('stage')) {
            // Some people rely on stage argument, so pass it to worker too.
            $arguments .= $this->input->getArgument('stage');
        }

        return $arguments;
    }
}
