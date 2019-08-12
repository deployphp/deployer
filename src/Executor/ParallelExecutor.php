<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Collection\PersistentCollection;
use Deployer\Configuration\Configuration;
use Deployer\Console\Application;
use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Host\Storage;
use Deployer\Component\Ssh\Client;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

const FRAMES = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

function spinner($message = '')
{
    $frame = FRAMES[(int)(microtime(true) * 10) % count(FRAMES)];
    return "  $frame $message\r";
}

class ParallelExecutor
{
    private $input;
    private $output;
    private $informer;
    private $console;
    private $client;
    private $config;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        Status $informer,
        Application $console,
        Client $client,
        Configuration $config
    )
    {
        $this->input = $input;
        $this->output = $output;
        $this->informer = $informer;
        $this->console = $console;
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * @param Host[] $hosts
     */
    private function connect(array $hosts)
    {
        $callback = function (string $output) {
            $output = preg_replace('/\n$/', '', $output);
            if (strlen($output) !== 0) {
                $this->output->writeln($output);
            }
        };

        // Connect to each host sequentially, to prevent getting locked.
        foreach ($hosts as $host) {
            if ($host instanceof Localhost) {
                continue;
            }
            $process = $this->getProcess($host, new Task('connect'));
            $process->start();

            while ($process->isRunning()) {
                $this->gatherOutput([$process], $callback);
                $this->output->write(spinner(str_pad("connect {$host->tag()}", intval(getenv('COLUMNS')) - 1)));
                usleep(1000);
            }
        }

        // Clear spinner.
        $this->output->write(str_repeat(' ', intval(getenv('COLUMNS')) - 1) . "\r");
    }

    /**
     * @param Task[] $tasks
     * @param Host[] $hosts
     */
    public function run(array $tasks, array $hosts): int
    {
        $this->persistHosts($hosts);
        $this->connect($hosts);

        $localhost = new Localhost();
        $limit = (int)$this->input->getOption('limit') ?: count($hosts);

        foreach ($tasks as $task) {
            $this->informer->startTask($task);

            if ($task->isLocal()) {
                Storage::load(...$hosts);
                {
                    $task->run(new Context($localhost, $this->input, $this->output));
                }
                Storage::flush(...$hosts);
            } else {
                foreach (array_chunk($hosts, $limit) as $chunk) {
                    $exitCode = $this->runTask($chunk, $task);
                    if ($exitCode !== 0) {
                        return $exitCode;
                    }
                }
            }

            $this->informer->endTask($task);
        }

        return 0;
    }

    private function runTask(array $hosts, Task $task): int
    {
        $processes = [];
        foreach ($hosts as $host) {
            if ($task->shouldBePerformed($host)) {
                $processes[] = $this->getProcess($host, $task);
                if ($task->isOnce()) {
                    $task->setHasRun();
                }
            }
        }

        $callback = function (string $output) {
            $output = preg_replace('/\n$/', '', $output);
            if (strlen($output) !== 0) {
                $this->output->writeln($output);
            }
        };

        $this->startProcesses($processes);

        while ($this->areRunning($processes)) {
            $this->gatherOutput($processes, $callback);
            $this->output->write(spinner());
            usleep(1000);
        }
        $this->output->write("\r");
        $this->gatherOutput($processes, $callback);

        return $this->gatherExitCodes($processes);
    }

    protected function getProcess(Host $host, Task $task): Process
    {
        $dep = PHP_BINARY . ' ' . DEPLOYER_BIN;
        $configFile = $host->get('worker-config');
        $decorated = $this->output->isDecorated() ? '--decorated' : '';
        $command = "$dep worker $task {$host->alias()} $configFile {$this->input} $decorated";

        if ($this->output->isDebug()) {
            $this->output->writeln("[{$host->tag()}] $command");
        }

        return Process::fromShellCommandline($command);
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
    protected function areRunning(array $processes): bool
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
     * @return void
     */
    protected function gatherOutput(array $processes, callable $callback)
    {
        foreach ($processes as $process) {
            $output = $process->getIncrementalOutput();
            if (strlen($output) !== 0) {
                $callback($output);
            }

            $errorOutput = $process->getIncrementalErrorOutput();
            if (strlen($errorOutput) !== 0) {
                $callback($errorOutput);
            }
        }
    }

    /**
     * Gather the cumulative exit code for the processes.
     */
    protected function gatherExitCodes(array $processes): int
    {
        foreach ($processes as $process) {
            if ($process->getExitCode() > 0) {
                return $process->getExitCode();
            }
        }

        return 0;
    }

    /**
     * @param Host[] $hosts
     */
    private function persistHosts(array $hosts)
    {
        foreach ($hosts as $host) {
            Context::push(new Context($host, $this->input, $this->output));

            $values = $host->getConfig()->persist();
            $workerConfig = sys_get_temp_dir() . '/' . uniqid('deployer-') . '-' . $host->alias() . '.dep';
            $values['worker-config'] = $workerConfig;

            $persistentCollection = new PersistentCollection($workerConfig, $values);
            $persistentCollection->flush();
            $host->getConfig()->setCollection($persistentCollection);

            Context::pop();
        }
    }
}
