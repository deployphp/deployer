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
    private $flatTree;

    /**
     * Depth of nesting (for rendering purposes)
     * @var int
     */
    private $depth = 0;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('debug:task');
        $this->setDescription('Display the task-tree for a given task');
        $this->deployer = $deployer;
        $this->flatTree = [];
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

        $rootTaskName = $input->getArgument('task');
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

        $this->buildTree($rootTaskName);
        $this->outputTree($rootTaskName);
    }

    /**
     * Index the before and after references for the whole tasklist
     */
    private function indexBeforeAfterNames()
    {
        $flatTaskList = $this->tasks;

//        foreach($flatTaskList as $t) {
//            if (count($t->getBefore())) {
//                var_dump($t->getName());
//                var_dump($t->getBefore());
//            }
//            if (count($t->getAfter())) {
//                var_dump($t->getName());
//                var_dump($t->getAfter());
//            }
//        }
//        exit;

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

        //TODO add indentation (give it as an argument)
        $prefix = '';
        // ├ ─

        // └ ─

        $postfix = '';

        var_dump($taskName);
        var_dump($this->beforeMap);
        var_dump(array_key_exists($taskName, $this->beforeMap));

        if (array_key_exists($taskName, $this->beforeMap)) {
            $postfix = sprintf('[before: %s]', $this->beforeMap[$taskName]);
        } elseif (array_key_exists($taskName, $this->afterMap)) {
            $postfix = sprintf('[after: %s]', $this->afterMap[$taskName]);
        }

        return sprintf('%s%s%s', $prefix, $taskName, $postfix);
    }

    private function buildTree($taskName)
    {
        $this->tasks = Deployer::get()->tasks;
        $this->createTreeFromTaskName($taskName);
    }

    /**
     * Create tree from the given taskname
     *
     * @param string $taskName
     * @param string $postfix
     */
    private function createTreeFromTaskName($taskName, $postfix = '')
    {
        $task = $this->tasks->get($taskName);

        if ($task->getBefore()) {
            $beforePostfix = sprintf('[before:%s]', $task->getName());

            foreach($task->getBefore() as $beforeTask) {
                $this->createTreeFromTaskName($beforeTask, $beforePostfix);
            }
        }

        if ($task instanceof GroupTask) {

            $this->addTaskToTree($task->getName(), true);

            $this->depth++;

            foreach($task->getGroup() as $subtask) {
                $this->createTreeFromTaskName($subtask);
            }

            $this->depth--;

        } else {
            $this->addTaskToTree($task->getName() . $postfix);
        }

        if ($task->getAfter()) {
            $afterPostfix = sprintf('[after:%s]', $task->getName());

            foreach($task->getAfter() as $afterTask) {
                $this->createTreeFromTaskName($afterTask, $afterPostfix);
            }
        }
    }

    private function addTaskToTree($taskName, $hasChildren = false) {
        $this->flatTree[] = ['taskName' => $taskName, 'depth' => $this->depth, 'hasChildren' => $hasChildren];
    }

    private function outputTree($taskName)
    {
        $this->output->writeln("The task-tree for <fg=cyan>$taskName</fg=cyan>:");

        foreach($this->flatTree as $treeItem) {
            $depth = $treeItem['depth'];

            $prefix = ($depth > 0 ? str_repeat('      ', $treeItem['depth'] - 1) : '') . '├──';

            $this->output->writeln(sprintf('%s %s', $prefix, $treeItem['taskName']));
        }
    }
}
