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
use Symfony\Component\Console\Application;
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
    public function __construct(Application $app, InputInterface $input, OutputInterface $output)
    {
        $this->app = $app;
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
        foreach (self::$tasks as $name => $task) {
            $command = new Command($name, $task);
            $this->app->add($command);
        }

        $this->app->run($this->input, $this->output);
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
}