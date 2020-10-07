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
    use CustomOption;

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
            'Set configuration option'
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
            'start-from',
            null,
            Option::VALUE_REQUIRED,
            'Task name to start execution from'
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

        $hosts = $this->selectHosts($input, $output);
        $this->applyOverrides($hosts, $input->getOption('option'));

        $plan = $input->getOption('plan') ? new Planner($output, $hosts) : null;

        $this->deployer->scriptManager->setHooksEnabled(!$input->getOption('no-hooks'));
        $startFrom = $input->getOption('start-from');
        if ($startFrom && !$this->deployer->tasks->has($startFrom)) {
            throw new Exception("Task ${startFrom} does not exist.");
        }
        $tasks = $this->deployer->scriptManager->getTasks($this->getName(), $startFrom);

        if (empty($tasks)) {
            throw new Exception('No task will be executed, because the selected hosts do not meet the conditions of the tasks');
        }

        if (!$plan) {
            $this->deployer->server->start();
            $this->deployer->master->connect($hosts);
        }
        $exitCode = $this->deployer->master->run($tasks, $hosts, $plan);

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
            $this->deployer->master->run($tasks, $hosts);
        }

        return $exitCode;
    }
}
