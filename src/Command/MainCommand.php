<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Configuration\Configuration;
use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Executor\Planner;
use Deployer\Utility\Httpie;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use function Deployer\Support\find_config_line;
use function Deployer\warning;

class MainCommand extends SelectCommand
{
    use CustomOption;
    use CommandCommon;

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
            'Start execution from this task'
        );
        $this->addOption(
            'log',
            null,
            Option::VALUE_REQUIRED,
            'Write log to a file'
        );
        $this->addOption(
            'profile',
            null,
            Option::VALUE_REQUIRED,
            'Write profile to a file'
        );
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;
        $this->deployer['log'] = $input->getOption('log');
        $this->telemetry([
            'project_hash' => empty($this->deployer->config['repository']) ? null : sha1($this->deployer->config['repository']),
            'hosts_count' => $this->deployer->hosts->count(),
            'recipes' => $this->deployer->config->get('recipes', []),
        ]);

        $hosts = $this->selectHosts($input, $output);
        $this->applyOverrides($hosts, $input->getOption('option'));

        // Save selected_hosts for selectedHosts() func.
        $hostsAliases = [];
        foreach ($hosts as $host) {
            $hostsAliases[] = $host->getAlias();
        }
        // Save selected_hosts per each host, and not globally. Otherwise it will
        // not be accessible for workers.
        foreach ($hosts as $host) {
            $host->set('selected_hosts', $hostsAliases);
        }

        $plan = $input->getOption('plan') ? new Planner($output, $hosts) : null;

        $this->deployer->scriptManager->setHooksEnabled(!$input->getOption('no-hooks'));
        $startFrom = $input->getOption('start-from');
        if ($startFrom && !$this->deployer->tasks->has($startFrom)) {
            throw new Exception("Task $startFrom does not exist.");
        }
        $skippedTasks = [];
        $tasks = $this->deployer->scriptManager->getTasks($this->getName(), $startFrom, $skippedTasks);

        if (empty($tasks)) {
            throw new Exception('No task will be executed, because the selected hosts do not meet the conditions of the tasks');
        }

        if (!$plan) {
            $this->checkUpdates();
            $this->deployer->server->start();

            if (!empty($skippedTasks)) {
                foreach ($skippedTasks as $taskName) {
                    $output->writeln("<fg=yellow;options=bold>skip</> $taskName");
                }
            }
        }
        $exitCode = $this->deployer->master->run($tasks, $hosts, $plan);

        if ($plan) {
            $plan->render();
            return 0;
        }

        if ($exitCode === 0) {
            $this->showBanner();
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

    private function checkUpdates()
    {
        try {
            fwrite(STDERR, Httpie::get('https://deployer.org/check-updates/' . DEPLOYER_VERSION)->send());
        } catch (\Throwable $e) {
            // Meh
        }
    }

    private function showBanner()
    {
        try {
            $withColors = '';
            if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
                $withColors = '_with_colors';
            }
            fwrite(STDERR, Httpie::get("https://deployer.medv.io/banners/" . $this->getName() . $withColors)->send());
        } catch (\Throwable $e) {
            // Meh
        }
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        parent::complete($input, $suggestions);
        if ($input->mustSuggestOptionValuesFor('start-from')) {
            $taskNames = [];
            foreach ($this->deployer->scriptManager->getTasks($this->getName()) as $task) {
                $taskNames[] = $task->getName();
            }
            $suggestions->suggestValues($taskNames);
        }
    }
}
