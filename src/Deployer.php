<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\RunTaskCommand;
use Deployer\Local\LocalInterface;
use Deployer\Server\ServerInterface;
use Deployer\Task\TaskInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Deployer
{
    /**
     * Singleton instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var LocalInterface
     */
    private $local;

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
    public static $tasks = [];

    /**
     * List of all servers.
     * @var ServerInterface[]
     */
    public static $servers = [];


    /**
     * Turn on/off multistage support
     * @var bool
     */
    public static $multistage = false;

    /**
     * Default deploy stage
     * @var string
     */
    public static $defaultStage = 'develop';

    /**
     * List of all stages.
     * @var array[]
     */
    public static $stages = [];

    /**
     * Array of global parameters.
     * @var array
     */
    public static $parameters = [];

    /**
     * @param Application $app
     * @param LocalInterface $local
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param HelperSet $helperSet
     */
    public function __construct(Application $app, LocalInterface $local, InputInterface $input, OutputInterface $output, HelperSet $helperSet = null)
    {
        $this->app = $app;
        $this->local = $local;
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
            $command = new RunTaskCommand($name, $task, $this);
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
     * @return LocalInterface
     */
    public function getLocal()
    {
        return $this->local;
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
    public static function getTask($name)
    {
        if (array_key_exists($name, self::$tasks)) {
            return self::$tasks[$name];
        } else {
            throw new \RuntimeException("Task \"$name\" does not defined.");
        }
    }
}