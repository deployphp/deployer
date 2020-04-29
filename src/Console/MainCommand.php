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
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
        $hooksEnabled = !$input->getOption('no-hooks');
        $this->deployer->config['log_file'] = $input->getOption('log');
        $hosts = $this->selectHosts($input, $output);
        $plan = $input->getOption('plan') ? new Planner($output, $hosts) : null;

        if ($plan === null) {
            // Materialize hosts configs
            $configDirectory = sprintf('%s/%s', sys_get_temp_dir(), uniqid());
            if (!is_dir($configDirectory)) {
                mkdir($configDirectory, 0700, true);
            }
            $this->deployer->config->set('config_directory', $configDirectory);
            foreach ($hosts as $alias => $host) {
                $host->getConfig()->save();
            }
        }

        $this->deployer->scriptManager->setHooksEnabled($hooksEnabled);
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
