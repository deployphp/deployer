<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\Command;
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
     * Singleton instance of deployer. It's can be accessed only after constructor call.
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
    public static $tasks = [];

    /**
     * List of all servers.
     * @var ServerInterface[]
     */
    public static $servers = [];

    /**
     * Array of global parameters.
     * @var array
     */
    public static $parameters = [];

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
            $command = new Command($name, $task);
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
}