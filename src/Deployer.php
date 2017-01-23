<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection;
use Deployer\Console\InitCommand;
use Deployer\Console\WorkerCommand;
use Deployer\Console\Application;
use Deployer\Server;
use Deployer\Stage\StageStrategy;
use Deployer\Task;
use Deployer\Console\TaskCommand;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console;

/**
 * @property Task\TaskCollection|Task\Task[] tasks
 * @property Server\ServerCollection|Server\ServerInterface[] servers
 * @property Server\EnvironmentCollection|Server\Environment[] environments
 * @property Collection config
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
            return new Collection();
        };
        $this->config['ssh_type'] = 'phpseclib';
        $this->config['default_stage'] = null;

        /******************************
         *            Core            *
         ******************************/

        $this['tasks'] = function () {
            return new Task\TaskCollection();
        };
        $this['servers'] = function () {
            return new Server\ServerCollection();
        };
        $this['environments'] = function () {
            return new Server\EnvironmentCollection();
        };
        $this['scriptManager'] = function ($c) {
            return new Task\ScriptManager($c['tasks']);
        };
        $this['stageStrategy'] = function ($c) {
            return new StageStrategy($c['servers'], $c['environments'], $c['config']['default_stage']);
        };
        $this['onFailure'] = function () {
            return new Collection();
        };

        /******************************
         *           Logger           *
         ******************************/

        $this['log_level'] = Logger::DEBUG;
        $this['log_handler'] = function () {
            return isset($this->config['log_file'])
                ? new StreamHandler($this->config['log_file'], $this['log_level'])
                : new NullHandler($this['log_level']);
        };
        $this['log'] = function () {
            $name = isset($this->config['log_name']) ? $this->config['log_name'] : 'Deployer';
            return new Logger($name, [
                $this['log_handler']
            ]);
        };

        /******************************
         *        Init command        *
         ******************************/

        $this['init_command'] = function () {
            return new InitCommand();
        };

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
            self::setDefault($name, array_merge_recursive($config, $array));
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
        $this->getConsole()->add($this['init_command']);

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

    /**
     * @return Task\ScriptManager
     */
    public function getScriptManager()
    {
        return $this['scriptManager'];
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this['log'];
    }
}
