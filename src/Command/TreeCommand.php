<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Task\GroupTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

class TreeCommand extends Command
{
    /**
     * @var Output
     */
    protected $output;
    /**
     * @var Deployer
     */
    private $deployer;
    /**
     * @var array
     */
    private $tree;
    /**
     * @var int
     */
    private $depth = 0;
    /**
     * @var array
     */
    private $openGroupDepths = [];

    public function __construct(Deployer $deployer)
    {
        parent::__construct('tree');
        $this->setDescription('Display the task-tree for a given task');
        $this->deployer = $deployer;
        $this->tree = [];
    }

    protected function configure()
    {
        $this->addArgument(
            'task',
            InputArgument::REQUIRED,
            'Task to display the tree for'
        );
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->output = $output;

        $rootTaskName = $input->getArgument('task');

        $this->buildTree($rootTaskName);
        $this->outputTree($rootTaskName);
        return 0;
    }

    private function buildTree(string $taskName)
    {
        $this->createTreeFromTaskName($taskName, '', true);
    }

    private function createTreeFromTaskName(string $taskName, string $postfix = '', bool $isLast = false)
    {
        $task = $this->deployer->tasks->get($taskName);

        if (!$task->isEnabled()) {
            if (empty($postfix)) {
                $postfix = '  // disabled';
            } else {
                $postfix .= '; disabled';
            }
        }

        if ($task->getBefore()) {
            $beforePostfix = sprintf("  // before %s", $task->getName());

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
            $afterPostfix = sprintf("  // after %s", $task->getName());

            foreach ($task->getAfter() as $afterTask) {
                $this->createTreeFromTaskName($afterTask, $afterPostfix);
            }
        }
    }

    private function addTaskToTree(string $taskName, bool $isLast = false)
    {
        $this->tree[] = [
            'taskName' => $taskName,
            'depth' => $this->depth,
            'isLast' => $isLast,
            'openDepths' => $this->openGroupDepths
        ];
    }

    private function outputTree(string $taskName)
    {
        $this->output->writeln("The task-tree for <info>$taskName</info>:");

        /**
         * @var int number of spaces for each depth increase
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

            $prefix .= $startSymbol . '──';

            $this->output->writeln(sprintf('%s %s', $prefix, $treeItem['taskName']));
        }
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);
        if ($input->mustSuggestArgumentValuesFor('task')) {
            $suggestions->suggestValues(array_keys($this->deployer->tasks->all()));
        }
    }
}
