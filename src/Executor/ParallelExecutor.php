<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Console\Output\VerbosityString;
use Deployer\Exception\Exception;
use Deployer\Server\Environment;
use Deployer\Server\Local;
use Deployer\Task\Context;
use Pure\Server;
use Pure\Storage\ArrayStorage;
use Pure\Storage\QueueStorage;
use React\Socket\ConnectionException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Process\Process;

class ParallelExecutor implements ExecutorInterface
{
    /**
     * Try to start server on this port.
     */
    const START_PORT = 3333;

    /**
     * If fails on start port, try until stop port.
     */
    const STOP_PORT = 3340;

    /**
     * @var InputDefinition
     */
    private $userDefinition;

    /**
     * @var \Deployer\Task\Task[]
     */
    private $tasks;

    /**
     * @var \Deployer\Server\ServerInterface[]
     */
    private $servers;

    /**
     * @var \Deployer\Server\Environment[]
     */
    private $environments;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var Informer
     */
    private $informer;

    /**
     * @var int
     */
    private $port;

    /**
     * @var Server
     */
    private $pure;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * Wait until all workers finish they tasks. When set this variable to true and send new tasks to workers.
     *
     * @var bool
     */
    private $wait = false;

    /**
     * @var QueueStorage
     */
    private $outputStorage;

    /**
     * @var QueueStorage
     */
    private $exceptionStorage;

    /**
     * Array will contain tasks list what workers has to before moving to next task.
     *
     * @var array
     */
    private $tasksToDo = [];

    /**
     * Check if current task was successfully finished on all server (no exception was triggered).
     *
     * @var bool
     */
    private $isSuccessfullyFinished = true;

    /**
     * Check if current task triggered a non-fatal exception.
     *
     * @var bool
     */
    private $hasNonFatalException = false;

    /**
     * @var Exception
     */
    private $lastException;

    /**
     * @var Local
     */
    private $localhost;

    /**
     * @var Environment
     */
    private $localEnv;

    /**
     * @param InputDefinition $userDefinition
     */
    public function __construct(InputDefinition $userDefinition)
    {
        $this->userDefinition = $userDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $environments, $input, $output)
    {
        $this->tasks = $tasks;
        $this->servers = $servers;
        $this->environments = $environments;
        $this->input = $input;
        $this->output = new OutputWatcher($output);
        $this->informer = new Informer($this->output);
        $this->localhost = new Local();
        $this->localEnv = new Environment();
        $this->port = self::START_PORT;

        connect:

        $this->pure = new Server($this->port);
        $this->loop = $this->pure->getLoop();

        // Start workers for each server.
        $this->loop->addTimer(0, [$this, 'startWorkers']);

        // Wait for output
        $this->outputStorage = $this->pure['output'] = new QueueStorage();
        $this->loop->addPeriodicTimer(0, [$this, 'catchOutput']);

        // Lookup for exception
        $this->exceptionStorage = $this->pure['exception'] = new QueueStorage();
        $this->loop->addPeriodicTimer(0, [$this, 'catchExceptions']);

        // Send workers tasks to do.
        $this->loop->addPeriodicTimer(0, [$this, 'sendTasks']);

        // Wait all workers finish they tasks.
        $this->loop->addPeriodicTimer(0, [$this, 'idle']);

        // Start loop
        try {
            $this->pure->run();
        } catch (ConnectionException $exception) {
            // If port is already used, try with another one.
            $output->writeln("<fg=red>✘ " . $exception->getMessage() . "</fg=red>");

            if (++$this->port <= self::STOP_PORT) {
                goto connect;
            }
        }

        if (!$this->isSuccessfullyFinished) {
            throw $this->lastException;
        }
    }

    /**
     * Start workers, put master port, server name to run on, and options stuff.
     */
    public function startWorkers()
    {
        // Get verbosity.
        $verbosity = new VerbosityString($this->output);

        // Get current deploy.php file.
        $deployPhpFile = $this->input->getOption('file');

        // User input.
        $input = '';

        // Get user arguments.
        foreach ($this->userDefinition->getArguments() as $argument) {
            $value = $this->input->getArgument($argument->getName());

            if ($value) {
                $input .= " $value";
            }
        }

        // Get user options.
        foreach ($this->userDefinition->getOptions() as $option) {
            $value = $this->input->getOption($option->getName());

            if ($value) {
                $input .= " --{$option->getName()} $value";
            }
        }

        foreach ($this->servers as $serverName => $server) {
            $process = new Process(
                "php " . DEPLOYER_BIN .
                (null === $deployPhpFile ? "" : " --file=$deployPhpFile") .
                " worker " .
                " --master 127.0.0.1:{$this->port}" .
                " --server $serverName" .
                " $input " .
                " $verbosity" .
                " &"
            );
            $process->disableOutput();
            $process->start();
        }
    }

    /**
     * Wait for output from workers.
     */
    public function catchOutput()
    {
        while (count($this->outputStorage) > 0) {
            list(, $messages, , $type) = $this->outputStorage->pop();

            $format = function ($message) {
                $message = rtrim($message, "\n");
                return implode("\n", array_map(function ($text) {
                    return $text;
                }, explode("\n", $message)));
            };

            $this->output->writeln(array_map($format, (array)$messages), $type);
        }
    }

    /**
     * Wait for exceptions from workers.
     */
    public function catchExceptions()
    {
        while (count($this->exceptionStorage) > 0) {
            list($serverName, $exceptionClass, $message) = $this->exceptionStorage->pop();

            // Print exception message.
            $this->informer->taskException($serverName, $exceptionClass, $message);

            // Save exception.
            $this->lastException = new $exceptionClass($message);

            // We got some exception, so not.
            $this->isSuccessfullyFinished = false;

            if ($exceptionClass == 'Deployer\Exception\NonFatalException') {

                // If we got NonFatalException, continue other tasks.
                $this->hasNonFatalException = true;
            } else {

                // Do not run other task.
                // Finish all current worker tasks and stop loop.
                $this->tasks = [];

                // Worker will not mark this task as done (remove self server name from `tasks_to_do` list),
                // so to finish current task execution we need to manually remove it from that list.
                $taskToDoStorage = $this->pure->getStorage('tasks_to_do');
                $taskToDoStorage->delete($serverName);
            }
        }
    }

    /**
     * Action time for master! Send tasks `to-do` for workers and go to sleep.
     * Also decide when to stop server/loop.
     */
    public function sendTasks()
    {
        if (!$this->wait) {
            if (count($this->tasks) > 0) {

                // Get task name to do.
                $task = current($this->tasks);
                $taskName = $task->getName();
                array_shift($this->tasks);

                $this->informer->startTask($taskName);

                if ($task->isOnce()) {
                    $task->run(new Context($this->localhost, $this->localEnv, $this->input, $this->output));
                    $this->informer->endTask();
                } else {
                    $this->tasksToDo = [];

                    foreach ($this->servers as $serverName => $server) {
                        if ($task->isOnServer($serverName)) {
                            if (!isset($this->environments[$serverName])) {
                                $this->environments[$serverName] = new Environment();
                            }

                            // Start task on $serverName.
                            $this->tasksToDo[$serverName] = $taskName;
                        }
                    }

                    // Inform all workers what tasks they need to do.
                    $taskToDoStorage = new ArrayStorage();
                    $taskToDoStorage->push($this->tasksToDo);
                    $this->pure->setStorage('tasks_to_do', $taskToDoStorage);

                    $this->wait = true;
                }
            } else {
                $this->loop->stop();
            }
        }
    }

    /**
     * While idle master, print information about finished tasks.
     */
    public function idle()
    {
        if ($this->wait) {
            $taskToDoStorage = $this->pure->getStorage('tasks_to_do');

            foreach ($this->tasksToDo as $serverName => $taskName) {
                if (!$taskToDoStorage->has($serverName)) {
                    $this->informer->endOnServer($serverName);
                    unset($this->tasksToDo[$serverName]);
                }
            }

            if (count($taskToDoStorage) === 0) {
                if ($this->isSuccessfullyFinished) {
                    $this->informer->endTask();
                } else {
                    $this->informer->taskError($this->hasNonFatalException);
                }

                // We waited all workers to finish their tasks.
                // Wait no more!
                $this->wait = false;

                if ($this->isSuccessfullyFinished || $this->hasNonFatalException) {
                    // Reset to default for next tasks.
                    $this->isSuccessfullyFinished = true;
                }
            }
        }
    }
}
