<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\InitCommand;
use Deployer\Console\WorkerCommand;
use Deployer\Console\Application;
use Deployer\Server;
use Deployer\Stage\StageStrategy;
use Deployer\Task;
use Deployer\Collection;
use Deployer\Console\TaskCommand;
use Pimple\Container;
use Symfony\Component\Console;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @property Task\TaskCollection|Task\Task[] $tasks
 * @property Task\Scenario\ScenarioCollection|Task\Scenario\Scenario[] $scenarios
 * @property Server\ServerCollection|Server\ServerInterface[] $servers
 * @property Server\EnvironmentCollection|Server\Environment[] $environments
 * @property Collection\Collection $parameters
 */
class Deployer
{
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Application $console
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    public function __construct(Application $console, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $container = new Container();

        $container['dispatcher'] = function () {
            return new EventDispatcher();
        };
        $container['console'] = function () use ($console) {
          return $console;
        };
        $container['input'] = function () use ($input) {
          return $input;
        };
        $container['output'] = function () use ($output) {
            return $output;
        };
        $container['tasks'] = function () {
            return new Task\TaskCollection();
        };
        $container['scenarios'] = function () {
            return new Task\Scenario\ScenarioCollection();
        };
        $container['servers'] = function () {
            return new Server\ServerCollection();
        };
        $container['environments'] = function () {
            return new Server\EnvironmentCollection();
        };
        $container['parameters'] = function () {
            return new Collection\Collection();
        };
        $container['stageStrategy'] = function ($c) {
            return new StageStrategy($c['servers'], $c['environments'], $c['parameters']);
        };

        $this->getDispatcher()->dispatch('init');
        $this->container = $container;
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
        $this->addConsoleCommands();
        
        $this->console->add(new WorkerCommand($this));
        $this->console->add(new InitCommand());

        $this->console->run($this->input, $this->output);
    }

    /**
     * Transform tasks to console commands.
     */
    public function addConsoleCommands()
    {
        $this->console->addUserArgumentsAndOptions();
        
        foreach ($this->tasks as $name => $task) {
            if ($task->isPrivate()) {
                continue;
            }
            
            $this->console->add(new TaskCommand($name, $task->getDescription(), $this));
        }
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->container['dispatcher'];
    }

    /**
     * @return Application
     */
    public function getConsole()
    {
        return $this->container['console'];
    }

    /**
     * @return Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this->container['input'];
    }

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->container['output'];
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (isset($this->container[$name])) {
            return $this->container[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    /**
     * @param string $name
     * @return Console\Helper\HelperInterface
     */
    public function getHelper($name)
    {
        return $this->getConsole()->getHelperSet()->get($name);
    }

    /**
     * @return StageStrategy
     */
    public function getStageStrategy()
    {
        return $this->container['stageStrategy'];
    }
}
