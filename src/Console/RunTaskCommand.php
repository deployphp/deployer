<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Environment;
use Deployer\Server\DryRun;
use Deployer\Server\ServerInterface;
use Deployer\Stage\Stage;
use Deployer\Task\AbstractTask;
use Deployer\Task\Runner;
use Deployer\Task\TaskInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunTaskCommand extends BaseCommand
{
    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param null|string $name
     * @param TaskInterface $task
     */
    public function __construct($name, TaskInterface $task, Deployer $deployer)
    {
        parent::__construct($name);
        $this->task = $task;
        $this->deployer = $deployer;

        if ($task instanceof AbstractTask) {
            $this->setDescription($task->getDescription());
        }

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Run without execution command on servers.'
        );

        $this->addArgument(
            'stage',
            InputArgument::OPTIONAL,
            'Run tasks for a specific environment',
            $deployer->getDefaultStage()
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Run without execution command on servers.'
        );

        $this->addOption(
            'server',
            null,
            InputOption::VALUE_OPTIONAL,
            'Run tasks only on ths server.'
        );

        if ($task instanceof AbstractTask) {
            foreach ($task->getOptions() as $option) {
                $this->getDefinition()->addOption($option);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Configure deployer to dry run.
        if ($input->getOption('dry-run')) {
            // Nothing to do now.
        }

        $servers = $this->deployer->getServers();

        if ($this->deployer->getMultistage()) {

            if (null === $input->getArgument('stage')) {
                throw new \InvalidArgumentException('You have turned on multistage support, but not defined a stage (or default stage).');
            }

            if (!$this->deployer->hasStage($input->getArgument('stage'))){
                throw new \InvalidArgumentException('This stage is not defined.');
            }

            $stage = $this->deployer->getStage($input->getArgument('stage'));

            $servers = $stage->getServers();

            foreach ($stage->getOptions() as $key => $value) {
                $this->deployer->setParameter($key, $value);
            }
        }

        try {

            foreach ($this->task->get() as $runner) {
                $isPrinted = $this->writeDesc($output, $runner->getDesc());

                $this->runSeries($runner, $servers, $input, $output);

                if ($isPrinted) {
                    $this->writeOk($output);
                }
            }

        } catch (\Exception $e) {
            $this->rollbackOnDeploy($servers, $input, $output);
            throw $e;
        }
    }

    /**
     * @param Runner $runner
     * @param ServerInterface[] $servers
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function runSeries(Runner $runner, $servers, InputInterface $input, OutputInterface $output)
    {
        $taskName = $runner->getName();
        $taskName = empty($taskName) ? 'UnNamed' : $taskName;

        /**
         * @var string $name
         * @var ServerInterface $server
         */
        foreach ($servers as $name => $server) {
            // Skip to specified server.
            $onServer = $input->getOption('server');
            if (null !== $onServer && $onServer !== $name) {
                continue;
            }

            // Convert server to dry run server.
            if ($input->getOption('dry-run')) {
                $server = new DryRun($server->getConfiguration());
            }

            // Set server environment.
            $env = $server->getEnvironment();
            $env->set('working_path', $server->getConfiguration()->getPath());
            Environment::setCurrent($env);

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln("Run task <comment>$taskName</comment> on server <info>{$name}</info>");
            }

            // Run task.
            $runner->run($input);
        }
    }

    /**
     * Print description of running task.
     * @param OutputInterface $output
     * @param string $desc
     * @return bool True if desc was printed.
     */
    private function writeDesc(OutputInterface $output, $desc)
    {
        if (OutputInterface::VERBOSITY_QUIET !== $output->getVerbosity() && !empty($desc)) {
            $output->write("<info>$desc</info>");

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->write("\n");
            } else {
                $tit = 60 - strlen($desc);
                $dots = str_repeat('.', $tit > 0 ? $tit : 0);
                $output->write("$dots");
            }

            return true;
        }

        return false;
    }

    /**
     * Print "ok" sign.
     * @param OutputInterface $output
     */
    private function writeOk(OutputInterface $output)
    {
        if (OutputInterface::VERBOSITY_QUIET !== $output->getVerbosity()) {
            $output->writeln("<info>✔</info>");
        }
    }

    /**
     * Rollback if something goes wrong.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function rollbackOnDeploy(array $servers, InputInterface $input, OutputInterface $output)
    {
        if (!$this->deployer->hasTask('deploy:rollback')) {
            return;
        }

        $task = $this->deployer->getTask('deploy:rollback');

        foreach ($task->get() as $runner) {
            $this->runSeries($runner, $servers, $input, $output);
        }
    }
}
