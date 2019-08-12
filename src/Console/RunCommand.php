<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use function Deployer\run;
use function Deployer\write;
use function Deployer\writeln;

class RunCommand extends Command
{
    private $deployer;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('run');
        $this->setDescription('Run any arbitrary command on hosts');
        $this->deployer = $deployer;
    }

    protected function configure()
    {
        $this->addArgument(
            'command-to-run',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'Command to run'
        );
        $this->addOption(
            'hosts',
            null,
            Option::VALUE_REQUIRED,
            'Host to deploy, comma separated, supports ranges [:]'
        );
        $this->addOption(
            'roles',
            null,
            Option::VALUE_REQUIRED,
            'Roles to deploy'
        );
    }

    protected function execute(Input $input, Output $output)
    {
        $command = implode(' ', $input->getArgument('command-to-run'));
        $byHosts = $input->getOption('hosts');
        $byRoles = $input->getOption('roles');

        if ($output->getVerbosity() === Output::VERBOSITY_NORMAL) {
            $output->setVerbosity(Output::VERBOSITY_VERBOSE);
        }

        foreach ($this->deployer->console->getUserDefinition()->getOptions() as $option) {
            if (!empty($input->getOption($option->getName()))) {
                $this->deployer->config[$option->getName()] = $input->getOption($option->getName());
            }
        }

        $hosts = $this->deployer->hostSelector->select($byHosts, $byRoles);
        if (empty($hosts)) {
            throw new Exception('No host selected');
        }

        $task = new Task($command, function () use ($command, $hosts) {
            run($command);
        });

        foreach ($hosts as $host) {
            try {
                $task->run(new Context($host, $input, $output));
            } catch (\Throwable $exception) {
                $this->deployer->informer->taskException($exception, $host);
            }
        }

        return 0;
    }
}
