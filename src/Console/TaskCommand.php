<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Executor\ExecutorInterface;
use Deployer\Executor\ParallelExecutor;
use Deployer\Executor\SeriesExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class TaskCommand extends Command
{

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var ExecutorInterface
     */
    public $executor;

    /**
     * @param string $name
     * @param string $description
     * @param Deployer $deployer
     */
    public function __construct($name, $description, Deployer $deployer)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->deployer = $deployer;
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addOption(
            'server',
            null,
            Option::VALUE_OPTIONAL,
            'Run tasks only on this server or group of servers.'
        );

        $this->addOption(
            'parallel',
            'p',
            Option::VALUE_NONE,
            'Run tests in parallel.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $tasks = [];
        foreach ($this->deployer->scenarios->get($this->getName())->getTasks() as $taskName) {
            $tasks[$taskName] = $this->deployer->tasks->get($taskName);
        }

        $serverName = $input->getOption('server');

        if (!empty($serverName)) {

            if ($this->deployer->serverGroups->has($serverName)) {
                $servers = array_map(function ($name) {
                    return $this->deployer->servers->get($name);
                }, $this->deployer->serverGroups->get($serverName));
            } else {
                $servers = [$serverName => $this->deployer->servers->get($serverName)];
            }
        } else {
            $servers = iterator_to_array($this->deployer->servers->getIterator());
        }

        if (empty($servers)) {
            throw new \RuntimeException('You need specify at least one server.');
        }

        $environments = iterator_to_array($this->deployer->environments);

        if (isset($this->executor)) {
            $executor = $this->executor;
        } else {
            if ($input->getOption('parallel')) {
                $executor = new ParallelExecutor();
            } else {
                $executor = new SeriesExecutor();
            }
        }

        $executor->run($tasks, $servers, $environments,  $input, $output);
    }
}
