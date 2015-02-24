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
use Symfony\Component\Console\Input\InputArgument;
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

        $stage = $input->hasArgument('stage') ? $input->getArgument('stage') : null;

        if (!empty($stage)) {

            $servers = [];
            
            // Look for servers which has in env `stages` current stage name.
            foreach($this->deployer->environments as $name => $env) {
                // If server does not have any stage category, skip them
                if (in_array($stage, $env->get('stages', []), true)) {
                    $servers[$name] = $this->deployer->servers->get($name);
                }
            }
            
            // If still is empty, try to find server by name. 
            if (empty($servers)) {
                if ($this->deployer->servers->has($stage)) {
                    $servers = [$stage => $this->deployer->servers->get($stage)];
                } else {
                    // Nothing found.
                    throw new \RuntimeException("Stage or server `$stage` does not found.");
                }
            }
            
        } else {
            // Otherwise run on all servers. 
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
                $executor = new ParallelExecutor($this->deployer->getConsole()->getUserDefinition());
            } else {
                $executor = new SeriesExecutor();
            }
        }

        $executor->run($tasks, $servers, $environments,  $input, $output);
    }
}
