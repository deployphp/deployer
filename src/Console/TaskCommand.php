<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\GracefulShutdownException;
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
        $this->addArgument(
            'hostname',
            InputArgument::OPTIONAL,
            'Hostname or stage'
        );
        $this->addOption(
            'parallel',
            'p',
            Option::VALUE_NONE,
            'Run tasks in parallel'
        );
        $this->addOption(
            'no-hooks',
            null,
            Option::VALUE_NONE,
            'Run task without after/before hooks'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $stage = $input->hasArgument('hostname') ? $input->getArgument('hostname') : null;
        $hooksEnabled = !$input->getOption('no-hooks');

        $hosts = $this->deployer->hostSelector->getHosts($stage);
        $tasks = $this->deployer->scriptManager->getTasks(
            $this->getName(),
            $hosts,
            $hooksEnabled
        );

        if ($input->getOption('parallel')) {
            $executor = new ParallelExecutor($this->deployer->getConsole()->getUserDefinition());
        } else {
            $executor = new SeriesExecutor();
        }

        try {
            $executor->run($tasks, $hosts, $input, $output);
        } catch (\Throwable $exception) {
            if (!($exception instanceof GracefulShutdownException)) {
                // Check if we have tasks to execute on failure
                if ($this->deployer['onFailure']->has($this->getName())) {
                    $taskName = $this->deployer['onFailure']->get($this->getName());
                    $tasks = $this->deployer->scriptManager->getTasks($taskName, $stage);
                    $executor->run($tasks, $hosts, $input, $output);
                }
            }
            throw $exception;
        }

        if (Deployer::hasDefault('terminate_message')) {
            $output->writeln(Deployer::getDefault('terminate_message'));
        }
    }
}
