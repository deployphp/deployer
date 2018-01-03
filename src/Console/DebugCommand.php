<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class DebugCommand extends Command
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('debug:task');
        $this->setDescription('Display the task-tree for a given task');
        $this->deployer = $deployer;
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addArgument(
            'task',
            InputArgument::REQUIRED,
            'Task to display the tree from'
        );
        $this->addArgument(
            'stage',
            InputArgument::OPTIONAL,
            'Stage or hostname'
        );
        $this->addOption(
            'hosts',
            null,
            Option::VALUE_REQUIRED,
            'Host to show the task-tree for, comma separated, supports ranges [:]'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $rootTask = $input->getArgument('task');
        $stage = $input->hasArgument('stage') ? $input->getArgument('stage') : null;
        $hosts = $input->getOption('hosts');

        if (!empty($hosts)) {
            $hosts = $this->deployer->hostSelector->getByHostnames($hosts);
        } else {
            $hosts = $this->deployer->hostSelector->getHosts($stage);
        }

        if (empty($hosts)) {
            throw new Exception('No host selected');
        }

        $tasks = Deployer::get()->scriptManager->getTasks($rootTask, $hosts);

        if (empty($tasks)) {
            throw new Exception('No task to be shown, because the selected hosts do not meet the conditions of the tasks');
        }

        $output->writeln("The task-tree for <fg=cyan>$rootTask</fg=cyan>:");

        $beforeMap = [];
        $afterMap = [];

        //index the before and after tasks
        foreach($tasks as $task) {
            $currentTaskName = $task->getName();

            foreach($task->getBefore() as $beforeTaskName) {
                $beforeMap[$beforeTaskName] = $currentTaskName;
            }
            foreach($task->getAfter() as $afterTaskName) {
                $afterMap[$afterTaskName] = $currentTaskName;
            }
        }

        foreach($tasks as $task) {
            $currentTaskName = $task->getName();
            $beforeAfterString = '';

            if (array_key_exists($currentTaskName, $beforeMap)) {
                $beforeAfterString = sprintf('[before: %s]', $beforeMap[$currentTaskName]);
            } elseif (array_key_exists($currentTaskName, $afterMap)) {
                $beforeAfterString = sprintf('[after: %s]', $afterMap[$currentTaskName]);
            }

            $output->writeln(sprintf(' - %s %s', $currentTaskName, $beforeAfterString));
        }
    }
}
