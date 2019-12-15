<?php
/* (c) Anton Medvedev <anton@medv.io>
/* (c) Oskar van Velden <oskar@rakso.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Task\GroupTask;
use Deployer\Task\TaskCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class DebugCommand extends Command
{
    /** @var Output */
    protected $output;

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
     * Depth of nesting (for rendering purposes)
     * @var int
     */
    private $depth = 0;

    /**
     * @var array
     */
    private $openGroupDepths = [];

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
            'Task to display the tree for'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $this->output = $output;

        $rootTaskName = $input->getArgument('task');

        $this->buildTree($rootTaskName);
        $this->outputTree($rootTaskName);
        return 0;
    }

    /**
     * Build the tree based on the given taskName
     * @param $taskName
     *
     * @return void
     */
    private function buildTree($taskName)
    {
        $this->tasks = Deployer::get()->tasks;
        $this->createTreeFromTaskName($taskName, '', true);
    }

    /**
     * Create a tree from the given taskname
     *
     * @param string $taskName
     * @param string $postfix
     * @param bool $isLast
     *
     * @return void
     */
    private function createTreeFromTaskName($taskName, $postfix = '', $isLast = false)
    {
        $task = $this->tasks->get($taskName);

        if ($task->getBefore()) {
            $beforePostfix = sprintf(' [before:%s]', $task->getName());

            foreach ($task->getBefore() as $beforeTask) {
                $this->createTreeFromTaskName($beforeTask, $beforePostfix);
            }
        }

        if ($task instanceof GroupTask) {
            $isLast = $isLast && empty($task->getAfter());

            $this->addTaskToTree($task->getName() . $postfix, $isLast);

            if (!$isLast) {
                $this->openGroupDepths[] = $this->depth;
            }

            $this->depth++;

            $taskGroup = $task->getGroup();
            foreach ($taskGroup as $subtask) {
                $isLastSubtask = $subtask === end($taskGroup);
                $this->createTreeFromTaskName($subtask, '', $isLastSubtask);
            }

            if (!$isLast) {
                array_pop($this->openGroupDepths);
            }

            $this->depth--;
        } else {
            $this->addTaskToTree($task->getName() . $postfix, $isLast);
        }

        if ($task->getAfter()) {
            $afterPostfix = sprintf(' [after:%s]', $task->getName());

            foreach ($task->getAfter() as $afterTask) {
                $this->createTreeFromTaskName($afterTask, $afterPostfix);
            }
        }
    }

    /**
     * Add the (formatted) taskName to the rendertree, with some additional information
     *
     * @param string $taskName formatted with prefixes if needed
     * @param bool $isLast indication for what symbol to use for rendering
     */
    private function addTaskToTree($taskName, $isLast = false)
    {
        $this->tree[] = [
            'taskName' => $taskName,
            'depth' => $this->depth,
            'isLast' => $isLast,
            'openDepths' => $this->openGroupDepths
        ];
    }

    /**
     * Render the tree, after everything is build
     *
     * @param $taskName
     */
    private function outputTree($taskName)
    {
        $this->output->writeln("The task-tree for <fg=cyan>$taskName</fg=cyan>:");

        /**
         * @var $REPEAT_COUNT number of spaces for each depth increase
         */
        $REPEAT_COUNT = 4;

        foreach ($this->tree as $treeItem) {
            $depth = $treeItem['depth'];

            $startSymbol = $treeItem['isLast'] || $treeItem === end($this->tree) ? '└' : '├';

            $prefix = '';

            for ($i = 0; $i < $depth; $i++) {
                if (in_array($i, $treeItem['openDepths'])) {
                    $prefix .= '│' . str_repeat(' ', $REPEAT_COUNT - 1);
                } else {
                    $prefix .= str_repeat(' ', $REPEAT_COUNT);
                }
            }

            $prefix .=  $startSymbol . '──';

            $this->output->writeln(sprintf('%s %s', $prefix, $treeItem['taskName']));
        }
    }
}
