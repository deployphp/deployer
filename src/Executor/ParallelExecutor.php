<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Server\ServerInterface;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Pure\Server;
use Pure\Storage\ArrayStorage;
use Pure\Storage\QueueStorage;
use React\Socket\ConnectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ParallelExecutor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function run($tasks, $servers, $environments, $input, $output)
    {
        $output = new OutputWatcher($output);
        $informer = new Informer($output);
        $port = 3333;

        start:
        $pure = new Server($port);
        $loop = $pure->getLoop();

        $outputStorage = $pure['output'] = new QueueStorage();
        $exceptionStorage = $pure['exception'] = new QueueStorage();

        // Wait until all workers finish they tasks. When set this variable to true and send new tasks to workers. 
        $wait = false;

        // Array will contain tasks list what workers has to before moving to next task. 
        $tasksToDo = [];

        // Check if current task was successfully finished on all server (no exception was triggered).
        $isSuccessfullyFinished = true;

        // Get verbosity.
        $verbosity = $this->getVerbosityString($output);

        // Get current deploy.php file.
        $deployPhpFile = $input->getOption('file');

        // Start workers for each server.
        $loop->addTimer(0, function () use (
            $servers,
            $port,
            $verbosity,
            $deployPhpFile
        ) {
            foreach ($servers as $serverName => $server) {
                $process = new Process(
                    "php " . DEPLOYER_BIN .
                    (null === $deployPhpFile ? "" : " --file=$deployPhpFile") .
                    " worker" .
                    " --master=127.0.0.1:$port" .
                    " --server=$serverName" .
                    " $verbosity" .
                    " &"
                );
                $process->disableOutput();
                $process->run();
            }
        });


        // Wait for output
        $loop->addPeriodicTimer(0, function () use ($output, $outputStorage) {
            while (count($outputStorage) > 0) {
                list($server, $messages, $newline, $type) = $outputStorage->pop();

                $format = function ($message) use ($server) {
                    $message = rtrim($message, "\n");
                    return implode("\n", array_map(function ($text) use ($server) {
                        return "[$server] $text";
                    }, explode("\n", $message)));

                };

                $output->writeln(array_map($format, (array)$messages), $type);
            }
        });

        // Send workers tasks to do.
        $loop->addPeriodicTimer(0, function () use (
            &$wait,
            &$tasks,
            &$tasksToDo,
            $servers,
            $informer,
            $input,
            $output,
            $loop,
            $pure
        ) {
            if (!$wait) {
                if (count($tasks) > 0) {
                    $task = current($tasks);
                    $taskName = key($tasks);
                    array_shift($tasks);

                    $informer->startTask($taskName);

                    if ($task->isOnce()) {
                        $task->run(new Context(null, null, $input, $output));
                        $informer->endTask();
                    } else {
                        $tasksToDo = [];

                        foreach ($servers as $serverName => $server) {
                            if ($task->runOnServer($serverName)) {
                                $informer->onServer($serverName);
                                $tasksToDo[$serverName] = $taskName;
                            }
                        }

                        // Inform all workers what tasks they need to do.
                        $taskToDoStorage = new ArrayStorage();
                        $taskToDoStorage->push($tasksToDo);
                        $pure->setStorage('tasks_to_do', $taskToDoStorage);

                        $wait = true;
                    }

                } else {
                    $loop->stop();
                }
            }
        });


        // Wait all workers finish they tasks.
        $loop->addPeriodicTimer(0, function () use (
            &$wait,
            &$tasksToDo,
            &$isSuccessfullyFinished,
            $pure,
            $informer
        ) {
            if ($wait) {
                $taskToDoStorage = $pure->getStorage('tasks_to_do');

                foreach ($tasksToDo as $serverName => $taskName) {
                    if (!$taskToDoStorage->has($serverName)) {
                        $informer->endOnServer($serverName);
                        unset($tasksToDo[$serverName]);
                    }
                }

                if (count($taskToDoStorage) === 0) {
                    if ($isSuccessfullyFinished) {
                        $informer->endTask();
                    } else {
                        $informer->taskError();
                    }
                    $wait = false;
                }
            }
        });


        // Lookup for exception
        $loop->addPeriodicTimer(0, function () use (
            &$tasks,
            &$isSuccessfullyFinished,
            $pure,
            $exceptionStorage,
            $tasksToDo,
            $loop,
            $output
        ) {
            while (count($exceptionStorage) > 0) {
                list($serverName, $exceptionClass, $message) = $exceptionStorage->pop();

                $message = "    $message    ";
                $output->writeln("");
                $output->writeln("<error>Exception [$exceptionClass] on [$serverName] server</error>");
                $output->writeln("<error>" . str_repeat(' ', strlen($message)) . "</error>");
                $output->writeln("<error>$message</error>");
                $output->writeln("<error>" . str_repeat(' ', strlen($message)) . "</error>");
                $output->writeln("");

                // Do not run other task.
                // Finish all current worker tasks and stop loop.
                $tasks = [];

                $isSuccessfullyFinished = false;

                $taskToDoStorage = $pure->getStorage('tasks_to_do');
                $taskToDoStorage->delete($serverName);
            }
        });


        // Start loop
        try {
            $pure->run();
        } catch (ConnectionException $exception) {
            // If port is already used, try with another one.
            $output->writeln("<error>" . $exception->getMessage() . "</error>");
            $port++;
            goto start;
        }
    }

    /**
     * @param OutputInterface $output
     * @return string
     */
    private function getVerbosityString(OutputInterface $output)
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_NORMAL:
                $verbosity = '';
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                $verbosity = '-v';
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbosity = '-vv';
                break;

            case OutputInterface::VERBOSITY_DEBUG:
                $verbosity = '-vvv';
                break;

            case OutputInterface::VERBOSITY_QUIET:
                $verbosity = '-q';
                break;

            default:
                $verbosity = '';
        }

        return $verbosity;
    }
} 