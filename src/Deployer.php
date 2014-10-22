<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Task\TaskInterface;
use Deployer\Server\ServerInterface;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Deployer
{
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @var Console
     */
    private $console;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Array of tasks where keys are tasks names.
     * @var TaskInterface[]
     */
    private $tasks = [];

    /**
     * Array of servers where keys are servers names.
     * @var ServerInterface[]
     */
    private $servers = [];

    /**
     * @param Console $app
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(Console $console, InputInterface $input, OutputInterface $output)
    {
        $this->console = $console;
        $this->input = $input;
        $this->output = $output;
        self::$instance = $this;
    }

    /**
     * @return Deployer
     */
    public static function get()
    {
        return self::$instance;
    }

    /**
     * Run console application.
     */
    public function run()
    {
        $this->transformTasksToConsoleCommands();

        $this->console->run($this->input, $this->output);
    }

    /**
     * Transform tasks to console commands. Run it before run of console app.
     */
    public function transformTasksToConsoleCommands()
    {
        foreach ($this->tasks as $name => $task) {
            $command = new RunTaskCommand($name, $task, $this);
            $this->console->add($command);
        }
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param TaskInterface $task
     */
    public function addTask(TaskInterface $task)
    {
        return $this->tasks[$task->getName()] = $task;
    }

    /**
     * Return task by name.
     * @param string $name
     * @return TaskInterface
     * @throws \RuntimeException if task does not defined.
     */
    public function getTask($name)
    {
        if ($this->hasTask($name)) {
            return $this->tasks[$name];
        } else {
            throw new \RuntimeException("Task `$name` does not defined.");
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasTask($name)
    {
        return array_key_exists($name, $this->tasks);
    }

    /**
     * @param ServerInterface $server
     */
    public function addServer(ServerInterface $server)
    {
        $this->servers[$server->getName()] = $server;
    }

    /**
     * @return ServerInterface
     * @throws \RuntimeException when server not found.
     */
    public function getServer($name)
    {
        if ($this->hasServer($name)) {
            return $this->servers[$name];
        } else {
            throw new \RuntimeException("Server `$name` does not defined.");
        }
    }

    /**
     * @return ServerInterface[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasServer($name)
    {
        return array_key_exists($name, $this->servers);
    }
}
