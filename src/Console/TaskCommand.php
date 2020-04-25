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
use Deployer\Host\Localhost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use function Deployer\localhost;

class TaskCommand extends Command
{
    protected $deployer;

    public function __construct(string $name, ?string $description, Deployer $deployer)
    {
        parent::__construct($name);
        if ($description) {
            $this->setDescription($description);
        }
        $this->deployer = $deployer;
    }

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
        $this->addOption(
            'profile',
            null,
            Option::VALUE_REQUIRED,
            'Writes tasks profile fo PROFILE file'
        );
    }

    protected function execute(Input $input, Output $output)
    {
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
        if (!$output->isDecorated()) {
            define('NO_ANSI', 'true');
        }

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

        if (empty($hosts) && empty($roles) && $this->deployer->config->has('default_roles')) {
            $roles = $this->deployer->config->get('default_roles');
        }

        $selectedHosts = $this->deployer->hostSelector->select($hosts, $roles);
        if (empty($selectedHosts)) {
            if ($this->deployer->hosts->count() > 0) {
                throw new Exception('No host selected');
            }
        }

        $tasks = $this->deployer->scriptManager->getTasks(
            $this->getName(),
            $selectedHosts,
            $hooksEnabled
        );
        if (empty($tasks)) {
            throw new Exception('No task will be executed, because the selected hosts do not meet the conditions of the tasks');
        }

        $exitCode = $this->deployer->executor->run($tasks, $selectedHosts);
        if ($exitCode === 0) {
            return 0;
        }
        if ($exitCode === GracefulShutdownException::EXIR_CODE) {
            return 1;
        }

        // Check if we have tasks to execute on failure.
        if ($this->deployer['fail']->has($this->getName())) {
            $taskName = $this->deployer['fail']->get($this->getName());
            $tasks = $this->deployer->scriptManager->getTasks(
                $taskName,
                $selectedHosts,
                $hooksEnabled
            );
            $this->deployer->executor->run($tasks, $selectedHosts);
        }

        return $exitCode;
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
