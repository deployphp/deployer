<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection as BaseCollection;
use Deployer\Console\WorkerCommand;
use Deployer\Console\Application;
use Deployer\Server;
use Deployer\Server\EnvironmentCollection;
use Deployer\Server\ServerCollection;
use Deployer\Stage\StageStrategy;
use Deployer\Task;
use Deployer\Collection;
use Deployer\Console\TaskCommand;
use Deployer\Task\Scenario\ScenarioCollection;
use Deployer\Task\TaskCollection;
use InvalidArgumentException;
use Symfony\Component\Console;

/**
 * @property TaskCollection|Task\Task[] $tasks
 * @property ScenarioCollection|Task\Scenario\Scenario[] $scenarios
 * @property ServerCollection|Server\ServerInterface[] $servers
 * @property EnvironmentCollection|Server\Environment[] $environments
 * @property BaseCollection $parameters
 */
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
     * @var BaseCollection
     */
    private $collections;

    /**
     * @var StageStrategy
     */
    private $stageStrategy;

    /**
     * @param Application $console
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     */
    public function __construct(Application $console, Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->console = $console;
        $this->input = $input;
        $this->output = $output;

        $this->collections = new BaseCollection();
        $this->collections['tasks'] = new TaskCollection();
        $this->collections['scenarios'] = new ScenarioCollection();
        $this->collections['servers'] = new ServerCollection();
        $this->collections['environments'] = new EnvironmentCollection();
        $this->collections['parameters'] = new BaseCollection();

        $this->stageStrategy = new StageStrategy($this->servers, $this->environments);

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
     * @throws InvalidArgumentException
     */
    public function __get($name)
    {
        if ($this->collections->has($name)) {
            return $this->collections[$name];
        } else {
            throw new InvalidArgumentException("Property \"$name\" does not exist.");
        }
    }

    /**
     * @param string $name
     * @return Console\Helper\HelperInterface
     */
    public function getHelper($name)
    {
        return $this->console->getHelperSet()->get($name);
    }

    /**
     * @return Application
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @return StageStrategy
     */
    public function getStageStrategy()
    {
        return $this->stageStrategy;
    }
}
