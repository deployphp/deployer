<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\RunTaskCommand;
use Deployer\Server\ServerInterface;
use Deployer\Stage\Stage;
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
     * Turn on/off multistage support.
     * @var bool
     */
    private $multistage = false;

    /**
     * Default deploy stage.
     * @var string
     */
    private $defaultStage = 'develop';

    /**
     * List of all stages.
     * @var Stage[]
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
     * @param HelperSet $helperSet
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
     * Get deploy-file from option or use default 'deploy.php'
     */
    public function requireDeployFile() {
        $inputoption = new InputOption('deploy-file', null, InputOption::VALUE_REQUIRED, 'The deploy file to use', getcwd() . '/deploy.php');
        $inputdefinition = $this->app->getDefinition();
        $inputdefinition->addOption($inputoption);

        $input = new ArgvInput();
        $input->bind($inputdefinition);
        $deployFile = $input->getOption('deploy-file');
        if (is_file($deployFile) && is_readable($deployFile)) {
            require $deployFile;
        }
    }

    /**
     * Transform tasks to console commands. Run it before run of console app.
     */
    public function transformTasksToConsoleCommands()
    {
        foreach ($this->tasks as $name => $task) {
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
     * @return HelperSet
     */
    public function getHelperSet()
    {
        return $this->helperSet;
    }

    /**
     * @param string $name
     * @param ServerInterface $server
     */
    public function addServer($name, ServerInterface $server)
    {
        $this->servers[$name] = $server;
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
            throw new \RuntimeException(sprintf('Server "%s" not found', $name));
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

    /**
     * @param string $key
     * @param mixed $default
     */
    public function getParameter($key, $default)
    {
        return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param boolean $multistage
     */
    public function setMultistage($multistage)
    {
        $this->multistage = $multistage;
    }

    /**
     * @return boolean
     */
    public function getMultistage()
    {
        return $this->multistage;
    }

    /**
     * @param string $defaultStage
     */
    public function setDefaultStage($defaultStage)
    {
        $this->defaultStage = $defaultStage;
    }

    /**
     * @return string
     */
    public function getDefaultStage()
    {
        return $this->defaultStage;
    }

    /**
     * @param string $name
     * @param Stage $stage
     */
    public function addStage($name, Stage $stage)
    {
        $this->stages[$name] = $stage;
    }

    /**
     * @param $name
     * @return Stage
     * @throws \RuntimeException
     */
    public function getStage($name)
    {
        if ($this->hasStage($name)) {
            return $this->stages[$name];
        } else {
            throw new \RuntimeException(sprintf('Stage "%s" not found', $name));
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasStage($name)
    {
        return array_key_exists($name, $this->stages);
    }

    /**
     * @return Stage[]
     */
    public function getStages()
    {
        return $this->stages;
    }

    /**
     * @param string $name
     * @param TaskInterface $task
     */
    public function addTask($name, TaskInterface $task)
    {
        return $this->tasks[$name] = $task;
    }

    /**
     * Return task by name.
     * @param string $name Task name.
     * @return TaskInterface
     * @throws \RuntimeException if task does not defined.
     */
    public function getTask($name)
    {
        if ($this->hasTask($name)) {
            return $this->tasks[$name];
        } else {
            throw new \RuntimeException("Task \"$name\" does not defined.");
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
}