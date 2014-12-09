<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Server;
use Deployer\Task;
use Deployer\Collection;
use Deployer\Console\TaskCommand;
use Symfony\Component\Console;

/**
 * @property Task\TaskCollection|Task\Task[] $tasks
 * @property Task\Scenario\ScenarioCollection|Task\Scenario\Scenario[] $scenarios
 * @property Server\ServerCollection|Server\ServerInterface[] $servers
 * @property Server\GroupCollection|array $serverGroups
 * @property Server\EnvironmentCollection|Server\Environment[] $environments
 */
class Deployer
{
    /**
     * Global instance of deployer. It's can be accessed only after constructor call.
     * @var Deployer
     */
    private static $instance;

    /**
     * @var Console\Application
     */
    private $console;

    /**
     * @var Console\Input\InputInterface
     */
    private $input;

    /**
     * @var Console\Output\OutputInterface
     */
    private $output;

    /**
     * @var Collection\Collection
     */
    private $collections;

    /**
     * @param Console\Application $console
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    public function __construct(Console\Application $console, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->console = $console;
        $this->input = $input;
        $this->output = $output;

        $this->collections = new Collection\Collection();
        $this->collections['tasks'] = new Task\TaskCollection();
        $this->collections['scenarios'] = new Task\Scenario\ScenarioCollection();
        $this->collections['servers'] = new Server\ServerCollection();
        $this->collections['serverGroups'] = new Server\GroupCollection();
        $this->collections['environments'] = new Server\EnvironmentCollection();

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

        $this->console->run($this->input, $this->output);
    }

    /**
     * Transform tasks to console commands. 
     */
    public function addConsoleCommands()
    {
        foreach ($this->tasks as $name => $task) {
            $this->console->add(new TaskCommand($name, $task->getDescription(), $this));
        }
    }

    /**
     * @return Console\Input\InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get($name)
    {
        if ($this->collections->has($name)) {
            return $this->collections[$name];
        } else {
            throw new \InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }
}
