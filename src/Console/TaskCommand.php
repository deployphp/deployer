<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Executor\ExecutorInterface;
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
            'stage',
            InputArgument::OPTIONAL,
            'Stage or hostname'
        );
        $this->addOption(
            'parallel',
            'p',
            Option::VALUE_NONE,
            'Run tasks in parallel'
        );
        $this->addOption(
            'limit',
            'l',
            Option::VALUE_REQUIRED,
            'How many host to run in parallel?'
        );
        $this->addOption(
            'no-hooks',
            null,
            Option::VALUE_NONE,
            'Run task without after/before hooks'
        );
        $this->addOption(
            'log',
            null,
            Option::VALUE_REQUIRED,
            'Log to file'
        );
        $this->addOption(
            'roles',
            null,
            Option::VALUE_REQUIRED,
            'Roles to deploy'
        );
        $this->addOption(
            'hosts',
            null,
            Option::VALUE_REQUIRED,
            'Host to deploy, comma separated, supports ranges [:]'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {
        $stage = $input->hasArgument('stage') ? $input->getArgument('stage') : null;
        $roles = $input->getOption('roles');
        $hosts = $input->getOption('hosts');

        $hooksEnabled = !$input->getOption('no-hooks');
        if (!empty($input->getOption('log'))) {
            $this->deployer->config['log_file'] = $input->getOption('log');
        }

        if (!empty($hosts)) {
            $hosts = $this->deployer->hostSelector->getByHostnames($hosts);
        } elseif (!empty($roles)) {
            $hosts = $this->deployer->hostSelector->getByRoles($roles);
        } else {
            $hosts = $this->deployer->hostSelector->getHosts($stage);
        }

        if (empty($hosts)) {
            throw new Exception('No host selected');
        }

        $tasks = $this->deployer->scriptManager->getTasks(
            $this->getName(),
            $hosts,
            $hooksEnabled
        );

        if (empty($tasks)) {
            throw new Exception('No task will be executed, because the selected hosts do not meet the conditions of the tasks');
        }

        if ($input->getOption('parallel')) {
            $executor = $this->deployer->parallelExecutor;
        } else {
            $executor = $this->deployer->seriesExecutor;
        }

        try {
            $executor->run($tasks, $hosts);
        } catch (\Throwable $exception) {
            if ($exception instanceof GracefulShutdownException) {
                throw $exception;
            } else {
                // Check if we have tasks to execute on failure
                if ($this->deployer['fail']->has($this->getName())) {
                    $taskName = $this->deployer['fail']->get($this->getName());
                    $tasks = $this->deployer->scriptManager->getTasks($taskName, $hosts, $hooksEnabled);

                    $executor->run($tasks, $hosts);
                }
                throw $exception;
            }
        }

        if (Deployer::hasDefault('terminate_message')) {
            $output->writeln(Deployer::getDefault('terminate_message'));
        }
    }
}
