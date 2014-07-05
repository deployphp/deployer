<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Server\Current;
use Deployer\Server\DryRun;
use Deployer\Task\AbstractTask;
use Deployer\Task\TaskInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * @param null|string $name
     * @param TaskInterface $task
     */
    public function __construct($name, TaskInterface $task)
    {
        parent::__construct($name);
        $this->task = $task;

        if ($task instanceof AbstractTask) {
            $this->setDescription($task->getDescription());
        }

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
            'Run tasks only on ths server.',
            null
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (OutputInterface::VERBOSITY_NORMAL <= $output->getVerbosity()) {
            $output->writeln("<info>"
                . (empty($this->getDescription()) ? $this->getName() : $this->getDescription())
                . "</info>"
            );
        }

        // Configure deployer to dry run.
        if ($input->getOption('dry-run')) {
            // Nothing to do now.
        }

        foreach (Deployer::$servers as $name => $server) {

            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln("Run task <info>{$this->getName()}</info> on server <info>{$name}</info>");
            }

            // Skip to specified server.
            $onServer = $input->getOption('server');
            if (null !== $onServer && $onServer !== $name) {
                continue;
            }

            // Convert server to dry run server.
            if ($input->getOption('dry-run')) {
                $server = new DryRun($server->getConfiguration());
            }

            // Set current server.
            Current::setServer($name, $server);

            // Run task.
            $this->task->run();
        }
    }
}