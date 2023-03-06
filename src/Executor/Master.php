<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Component\Ssh\Client;
use Deployer\Component\Ssh\IOArguments;
use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Selector\Selector;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

const FRAMES = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

function spinner(string $message = ''): string
{
    $frame = FRAMES[(int) ((new \DateTime)->format('u') / 1e5) % count(FRAMES)];
    return "  $frame $message\r";
}

class Master
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
     * @var Server
     */
    private $server;

    /**
     * @var Messenger
     */
    private $messenger;

    /**
     * @var false|string
     */
    private $phpBin;

    public function __construct(
        InputInterface  $input,
        OutputInterface $output,
        Server          $server,
        Messenger       $messenger
    )
    {
        $this->input = $input;
        $this->output = $output;
        $this->server = $server;
        $this->messenger = $messenger;
        $this->phpBin = (new PhpExecutableFinder())->find();
    }

    /**
     * @param Task[] $tasks
     * @param Host[] $hosts
     */
    public function run(array $tasks, array $hosts, ?Planner $plan = null): int
    {
        $globalLimit = (int)$this->input->getOption('limit') ?: count($hosts);

        foreach ($tasks as $task) {
            if (!$plan) {
                $this->messenger->startTask($task);
            }

            $plannedHosts = $hosts;

            $limit = min($globalLimit, $task->getLimit() ?? $globalLimit);

            if ($task->isOnce()) {
                $plannedHosts = [];
                foreach ($hosts as $currentHost) {
                    if (Selector::apply($task->getSelector(), $currentHost)) {
                        $plannedHosts[] = $currentHost;
                        break;
                    }
                }
            } else if ($task->isOncePerNode()) {
                $plannedHosts = [];
                foreach ($hosts as $currentHost) {
                    if (Selector::apply($task->getSelector(), $currentHost)) {
                        $nodeLabel = $currentHost->getHostname();
                        $labels = $currentHost->config()->get('labels', []);
                        if (is_array($labels) && array_key_exists('node', $labels)) {
                            $nodeLabel = $labels['node'];
                        }
                        if (array_key_exists($nodeLabel, $plannedHosts)) {
                            continue;
                        }
                        $plannedHosts[$nodeLabel] = $currentHost;
                    }
                }
            }

            if ($limit === 1 || count($plannedHosts) === 1) {
                foreach ($plannedHosts as $currentHost) {
                    if (!Selector::apply($task->getSelector(), $currentHost)) {
                        if ($plan) {
                            $plan->commit([], $task);
                        }
                        continue;
                    }

                    if ($plan) {
                        $plan->commit([$currentHost], $task);
                        continue;
                    }

                    $exitCode = $this->runTask($task, [$currentHost]);
                    if ($exitCode !== 0) {
                        return $exitCode;
                    }
                }
            } else {
                foreach (array_chunk($plannedHosts, $limit) as $chunk) {
                    $selectedHosts = [];
                    foreach ($chunk as $currentHost) {
                        if (Selector::apply($task->getSelector(), $currentHost)) {
                            $selectedHosts[] = $currentHost;
                        }
                    }

                    if ($plan) {
                        $plan->commit($selectedHosts, $task);
                        continue;
                    }

                    $exitCode = $this->runTask($task, $selectedHosts);
                    if ($exitCode !== 0) {
                        return $exitCode;
                    }
                }
            }

            if (!$plan) {
                $this->messenger->endTask($task);
            }
        }

        return 0;
    }

    /**
     * @param Host[] $hosts
     */
    private function runTask(Task $task, array $hosts): int
    {
        if (getenv('DEPLOYER_LOCAL_WORKER') === 'true') {
            // This allows to code coverage all recipe,
            // as well as speedup tests by not spawning
            // lots of processes. Also there is a few tests
            // what runs with workers for tests subprocess
            // communications.
            foreach ($hosts as $host) {
                $worker = new Worker(Deployer::get());
                $exitCode = $worker->execute($task, $host);
                if ($exitCode !== 0) {
                    $this->messenger->endTask($task, true);
                    return $exitCode;
                }
            }
            return 0;
        }

        $callback = function (string $output) {
            $output = preg_replace('/\n$/', '', $output);
            if (strlen($output) !== 0) {
                $this->output->writeln($output);
            }
        };

        /** @var Process[] $processes */
        $processes = [];

        $this->server->loop->futureTick(function () use (&$processes, $hosts, $task) {
            foreach ($hosts as $host) {
                $processes[] = $this->createProcess($host, $task);
            }

            foreach ($processes as $process) {
                $process->start();
            }
        });

        $this->server->loop->addPeriodicTimer(0.03, function ($timer) use (&$processes, $callback) {
            $this->gatherOutput($processes, $callback);
            if ($this->output->isDecorated() && !getenv('CI')) {
                $this->output->write(spinner());
            }
            if ($this->allFinished($processes)) {
                $this->server->loop->stop();
                $this->server->loop->cancelTimer($timer);
            }
        });

        $this->server->loop->run();

        if ($this->output->isDecorated() && !getenv('CI')) {
            $this->output->write("    \r"); // clear spinner
        }
        $this->gatherOutput($processes, $callback);

        if ($this->cumulativeExitCode($processes) !== 0) {
            $this->messenger->endTask($task, true);
        }

        return $this->cumulativeExitCode($processes);
    }

    protected function createProcess(Host $host, Task $task): Process
    {
        $command = [
            $this->phpBin, DEPLOYER_BIN,
            'worker', '--port', $this->server->getPort(),
            '--task', $task,
            '--host', $host->getAlias(),
        ];
        $command = array_merge($command, IOArguments::collect($this->input, $this->output));
        if ($task->isVerbose() && $this->output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
            $command[] = '-v';
        }
        if ($this->output->isDebug()) {
            $this->output->writeln("[$host] " . join(' ', $command));
        }
        return new Process($command);
    }

    /**
     * @param Process[] $processes
     */
    protected function allFinished(array $processes): bool
    {
        foreach ($processes as $process) {
            if (!$process->isTerminated()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Process[] $processes
     */
    protected function gatherOutput(array $processes, callable $callback): void
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
     * @param Process[] $processes
     */
    protected function cumulativeExitCode(array $processes): int
    {
        foreach ($processes as $process) {
            if ($process->getExitCode() > 0) {
                return $process->getExitCode();
            }
        }
        return 0;
    }
}
