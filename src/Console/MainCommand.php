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
use Deployer\Executor\Planner;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;

class MainCommand extends SelectCommand
{
    public function __construct(string $name, ?string $description, Deployer $deployer)
    {
        parent::__construct($name, $deployer);
        if ($description) {
            $this->setDescription($description);
        }
    }

    protected function configure()
    {
        parent::configure();

        // Add global options defined with `option()` func.
        $this->getDefinition()->addOptions($this->deployer->inputDefinition->getOptions());
        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Sets configuration option'
        );
        $this->addOption(
            'limit',
            'l',
            Option::VALUE_REQUIRED,
            'How many tasks to run in parallel?'
        );
        $this->addOption(
            'no-hooks',
            null,
            Option::VALUE_NONE,
            'Run tasks without after/before hooks'
        );
        $this->addOption(
            'plan',
            null,
            Option::VALUE_NONE,
            'Show execution plan'
        );
        $this->addOption(
            'log',
            null,
            Option::VALUE_REQUIRED,
            'Log to file'
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
        $this->deployer->input = $input;
        $this->deployer->output = $output;
        $this->deployer->config['log_file'] = $input->getOption('log');
        $this->parseOptions($input->getOption('option'));

        $hosts = $this->selectHosts($input, $output);

        $plan = $input->getOption('plan') ? new Planner($output, $hosts) : null;
        if ($plan === null) {
            // Materialize hosts configs
            $configDirectory = sprintf('%s/deployer/%s/%s', sys_get_temp_dir(), uniqid(), time());
            if (!is_dir($configDirectory)) {
                mkdir($configDirectory, 0700, true);
            }
            $this->deployer->config->set('config_directory', $configDirectory);
            foreach ($hosts as $alias => $host) {
                $host->getConfig()->save();
            }
        }

        $this->deployer->scriptManager->setHooksEnabled(!$input->getOption('no-hooks'));
        $tasks = $this->deployer->scriptManager->getTasks($this->getName());
        if (empty($tasks)) {
            throw new Exception('No task will be executed, because the selected hosts do not meet the conditions of the tasks');
        }

        $exitCode = $this->deployer->executor->run($tasks, $hosts, $plan);

        if ($plan) {
            $plan->render();
            return 0;
        }

        if ($exitCode === 0) {
            return 0;
        }
        if ($exitCode === GracefulShutdownException::EXIT_CODE) {
            return 1;
        }

        // Check if we have tasks to execute on failure.
        if ($this->deployer['fail']->has($this->getName())) {
            $taskName = $this->deployer['fail']->get($this->getName());
            $tasks = $this->deployer->scriptManager->getTasks($taskName);
            $this->deployer->executor->run($tasks, $hosts);
        }

        return $exitCode;
    }

    protected function parseOptions(array $options)
    {
        foreach ($options as $option) {
            list($name, $value) = explode('=', $option);
            $value = $this->castValueToPhpType(trim($value));
            $this->deployer->config->set(trim($name), $value);
        }
    }

    protected function castValueToPhpType($value)
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
