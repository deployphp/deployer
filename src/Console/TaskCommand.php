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
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Throwable;

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
            'hosts',
            null,
            Option::VALUE_REQUIRED,
            'Hosts to deploy, comma separated, supports ranges [:]'
        );
        $this->addOption(
            'roles',
            null,
            Option::VALUE_REQUIRED,
            'Roles to deploy'
        );
        $this->addOption(
            'stage',
            null,
            Option::VALUE_REQUIRED,
            'Stage or hostname'
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
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Sets configuration option'
        );
    }

    /**
     * @param Input $input
     * @param Output $output
     *
     * @throws Throwable
     */
    protected function execute(Input $input, Output $output)
    {
        $stage = $input->getOption('stage');
        $roles = $input->getOption('roles');
        $hosts = $input->getOption('hosts');
        $logFile = $input->getOption('log');
        $hooksEnabled = !$input->getOption('no-hooks');

        foreach ($this->deployer->console->getUserDefinition()->getOptions() as $option) {
            if (!empty($input->getOption($option->getName()))) {
                $this->deployer->config[$option->getName()] = $input->getOption($option->getName());
            }
        }

        $this->parseOptions($input->getOption('option'));

        $this->deployer->config['log_file'] = $logFile;
        $this->deployer->config['decorated'] = $output->isDecorated();
        $this->deployer->config['verbosity'] = $output->getVerbosity();

        $hosts = array_values($this->deployer->hosts->toArray());
        // TODO: selector
//        if (!empty($hosts)) {
//            $hosts = $this->deployer->hostSelector->getByHostnames($hosts);
//        } elseif (!empty($roles)) {
//            $hosts = $this->deployer->hostSelector->getByRoles($roles);
//        } else {
//            $hosts = $this->deployer->hostSelector->getHosts($stage);
//        }

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

        try {
            $this->deployer->executor->run($tasks, $hosts);
        } catch (Throwable $exception) {
            $this->deployer->logger->log('[' . get_class($exception) . '] ' . $exception->getMessage());
            $this->deployer->logger->log($exception->getTraceAsString());

            if ($exception instanceof GracefulShutdownException) {
                throw $exception;
            } else {
                // Check if we have tasks to execute on failure
                if ($this->deployer['fail']->has($this->getName())) {
                    $taskName = $this->deployer['fail']->get($this->getName());
                    $tasks = $this->deployer->scriptManager->getTasks($taskName, $hosts, $hooksEnabled);

                    $this->deployer->executor->run($tasks, $hosts);
                }
                throw $exception;
            }
        }
    }

    private function parseOptions(array $options)
    {
        foreach ($options as $option) {
            list($name, $value) = explode('=', $option);
            $value = $this->castValueToPhpType($value);
            $this->deployer->config->set($name, $value);
        }
    }

    private function castValueToPhpType($value)
    {
        switch ($value) {
            case 'true':
                return true;
            case 'false':
                return false;
            default:
                return $value;
        }
    }
}
