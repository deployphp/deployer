<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\RunTaskCommand;
use Deployer\Server\ServerInterface;
use Deployer\Task\TaskFactory;
use Deployer\Task\TaskInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
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
     * @var Application
     */
    private $app;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var HelperSet
     */
    private $helperSet;

    /**
     * List of all tasks.
     * @var TaskInterface[]
     */
    private $tasks = [];

    /**
     * List of all servers.
     * @var ServerInterface[]
     */
    private $servers = [];

    /**
     * Turn on/off multistage support
     * @var bool
     */
    private $multistage = false;

    /**
     * Default deploy stage
     * @var string
     */
    private $defaultStage = 'develop';

    /**
     * List of all stages.
     * @var array[]
     */
    private $stages = [];

    /**
     * Array of global parameters.
     * @var array
     */
    private $parameters = [];

    /**
     * @param Application $app
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(Application $app, InputInterface $input, OutputInterface $output, HelperSet $helperSet = null)
    {
        $this->app = $app;
        $this->input = $input;
        $this->output = $output;
        $this->helperSet = null === $helperSet ? $app->getHelperSet() : $helperSet;
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

        $this->app->run($this->input, $this->output);
    }

    /**
     * Transform tasks to console commands. Run it before run of console app.
     */
    public function transformTasksToConsoleCommands()
    {
        foreach (self::$tasks as $name => $task) {
            $command = new RunTaskCommand($name, $task);
            $this->app->add($command);
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
     * @return Application
     */
    public function getConsole()
    {
        return $this->app;
    }

    /**
     * @return HelperSet
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * Return task by name.
     * @param string $name Task name.
     * @return TaskInterface
     * @throws \RuntimeException If task does not defined.
     */
    public function getTask($name)
    {
        if (array_key_exists($name, $this->tasks)) {
            return $this->tasks[$name];
        } else {
            throw new \RuntimeException("Task \"$name\" does not defined.");
        }
    }
}