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
use Deployer\Console\TaskCommand;
use Deployer\Type\DotArray;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Symfony\Component\Console;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @property Task\TaskCollection|Task\Task[] tasks
 * @property Task\Scenario\ScenarioCollection|Task\Scenario\Scenario[] scenarios
 * @property Server\ServerCollection|Server\ServerInterface[] servers
 * @property Server\EnvironmentCollection|Server\Environment[] environments
 * @property DotArray config
 */
class Deployer extends Container
{
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @param Application $console
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    public function __construct(Application $console, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        parent::__construct();

        /******************************
         *         Dispatcher         *
         ******************************/

        $this['dispatcher'] = function () {
            return new EventDispatcher();
        };


        /******************************
         *           Console          *
         ******************************/

        $this['console'] = function () use ($console) {
            return $console;
        };
        $this['input'] = function () use ($input) {
            return $input;
        };
        $this['output'] = function () use ($output) {
            return $output;
        };

        /******************************
         *           Config           *
         ******************************/

        $this['config'] = function () {
            return new DotArray();
        };
        $this->config['ssh_type'] = 'phpseclib';
        $this->config['default_stage'] = null;


        /******************************
         *            Core            *
         ******************************/

        $this['tasks'] = function () {
            return new Task\TaskCollection();
        };
        $this['scenarios'] = function () {
            return new Task\Scenario\ScenarioCollection();
        };
        $this['servers'] = function () {
            return new Server\ServerCollection();
        };
        $this['environments'] = function () {
            return new Server\EnvironmentCollection();
        };
        $this['stageStrategy'] = function ($c) {
            return new StageStrategy($c['servers'], $c['environments'], $c['config']['default_stage']);
        };

        /******************************
         *           Logger           *
         ******************************/

        $this['log_level'] = function ($c) {
            $parameters = $c['parameters'];
            return isset($parameters['log_level']) ? $parameters['log_level'] : Logger::ERROR;
        };
        $this['log_handler'] = function ($c) {
            $parameters = $c['parameters'];
            return new StreamHandler($parameters['log_file'], $parameters['log_level']);
        };
        $this['log'] = function ($c) {
            $parameters = $c['parameters'];
            $name = isset($parameters['log_name']) ? $parameters['log_name'] : 'Deployer';
            return new Logger($name);
        };

        self::$instance = $this;
        $this->getDispatcher()->dispatch('init');
    }

    /**
     * @return Deployer
     */
    public static function get()
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public static function setDefault($name, $value)
    {
        Deployer::get()->config[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getDefault($name, $default = null)
    {
        return self::hasDefault($name) ? Deployer::get()->config[$name] : $default;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function hasDefault($name)
    {
        return isset(Deployer::get()->config[$name]);
    }

    /**
     * @param string $name
     * @param array $array
     */
    public static function addDefault($name, $array)
    {
        if (self::hasDefault($name)) {
            $config = self::getDefault($name);
            if (!is_array($config)) {
                throw new \RuntimeException("Configuration parameter `$name` isn't array.");
            }
            self::setDefault($name, array_merge($config, $array));
        } else {
            self::setDefault($name, $array);
        }
    }

    /**
     * Run console application.
     */
    public function run()
    {
        $this->addConsoleCommands();

        $this->getConsole()->add(new WorkerCommand($this));
        $this->getConsole()->add(new InitCommand());

        $this->getConsole()->run($this->input, $this->output);
    }

    /**
     * Transform tasks to console commands.
     */
    public function addConsoleCommands()
    {
        $this->getConsole()->addUserArgumentsAndOptions();

        foreach ($this->tasks as $name => $task) {
            if ($task->isPrivate()) {
                continue;
            }

            $this->getConsole()->add(new TaskCommand($name, $task->getDescription(), $this));
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if (isset($this[$name])) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this['dispatcher'];
    }

    /**
     * @return Application
     */
    public function getConsole()
    {
        return $this['console'];
    }

    /**
     * @return Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this['input'];
    }

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this['output'];
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
        return $this['stageStrategy'];
    }
}
