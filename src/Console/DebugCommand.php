<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Host\Host;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use Deployer\Task\TaskCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class DebugCommand extends Command
{
    /** @var Output */
    protected $output;

    /**
     * @var string
     */
    private $rootTaskName;

    /**
     * @var array
     */
    private $beforeMap;

    /**
     * @var array
     */
    private $afterMap;

    /**
     * @var TaskCollection
     */
    private $tasks;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var array
     */
    private $tree;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('debug:task');
        $this->setDescription('Display the task-tree for a given task');
        $this->deployer = $deployer;
        $this->tree = [];
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
        $this->addOption(
            'no-hooks',
            null,
            Option::VALUE_NONE,
            'Debug task without after/before hooks'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $this->output = $output;

        $this->rootTaskName = $input->getArgument('task');
        $stage = $input->hasArgument('stage') ? $input->getArgument('stage') : null;
        $hosts = $input->getOption('hosts');
        $hooksEnabled = !$input->getOption('no-hooks');

        if (!empty($hosts)) {
            $hosts = $this->deployer->hostSelector->getByHostnames($hosts);
        } else {
            $hosts = $this->deployer->hostSelector->getHosts($stage);
        }

        if (empty($hosts)) {
            throw new Exception('No host selected');
        }

        //TODO the following should also be in a seperate kickStart-method
        $this->tasks = Deployer::get()->tasks;
        $rootTask = $this->tasks->get($this->rootTaskName);

        if (! ($rootTask instanceof GroupTask) ) {
            $output->writeln("The task-tree for <fg=cyan>$this->rootTaskName</fg=cyan>:");
            $output->writeln(" $rootTask->getName()");

            //bail out, it was no GroupTask
            return;
        }

        $this->indexBeforeAfterNames($hosts);

        $this->createTreeFromTask($this->rootTaskName);

        $this->outputTree();
    }

    private function buildTree($flat, $pidKey, $idKey = null)
    {
        $grouped = array();
        foreach ($flat as $sub){
            $grouped[$sub[$pidKey]][] = $sub;
        }

        $fnBuilder = function($siblings) use (&$fnBuilder, $grouped, $idKey) {
            foreach ($siblings as $k => $sibling) {
                $id = $sibling[$idKey];
                if(isset($grouped[$id])) {
                    $sibling['children'] = $fnBuilder($grouped[$id]);
                }
                $siblings[$k] = $sibling;
            }

            return $siblings;
        };

        $tree = $fnBuilder($grouped[0]);

        return $tree;
    }

    /**
     * Index the before and after references for the whole tasklist
     *
     * @param Host[] $hosts
     */
    private function indexBeforeAfterNames($hosts)
    {
        $flatTaskList = Deployer::get()->scriptManager->getTasks($this->rootTaskName, $hosts);

        $this->beforeMap = [];
        $this->afterMap = [];

        //index the before and after tasks
        foreach($flatTaskList as $task) {
            $currentTaskName = $task->getName();

            foreach($task->getBefore() as $beforeTaskName) {
                $this->beforeMap[$beforeTaskName] = $currentTaskName;
            }
            foreach($task->getAfter() as $afterTaskName) {
                $this->afterMap[$afterTaskName] = $currentTaskName;
            }
        }
    }

    /**
     * Decorate the taskName with possible before or after reference
     *
     * @param Task $task
     * @return string
     */
    private function decorateTaskName(Task $task) {
        $taskName = $task->getName();

        $beforeAfterString = '';

        if (array_key_exists($taskName, $this->beforeMap)) {
            $beforeAfterString = sprintf('[before: %s]', $this->beforeMap[$taskName]);
        } elseif (array_key_exists($taskName, $this->afterMap)) {
            $beforeAfterString = sprintf('[after: %s]', $this->afterMap[$taskName]);
        }

        //TODO add indentation (give it as an argument)
        return sprintf('%s %s', $taskName, $beforeAfterString);
    }

    /**
     * Create tree from the given taskname
     *
     * @param string $taskName
     */
    private function createTreeFromTask($taskName)
    {
        $task = $this->tasks->get($taskName);

//            if ($task instanceof GroupTask) {
//                foreach($task->getGroup() as $subtask) {
//                    $tree[] = $makeTree($subtask);
//                }
//            } else {
        $this->tree[] = $this->decorateTaskName($task);
//            }
    }

    private function outputTree()
    {
        $this->output->writeln("The task-tree for <fg=cyan>$this->rootTaskName</fg=cyan>:");

        var_dump($this->tree);
        exit;
    }
}
