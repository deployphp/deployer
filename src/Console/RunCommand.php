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
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct('run');
        $this->setDescription('Run any arbitrary command on hosts');
        $this->deployer = $deployer;
    }

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->addArgument(
            'command-to-run',
            InputArgument::REQUIRED,
            'Command to run'
        );
        $this->addOption(
            'log',
            null,
            Option::VALUE_REQUIRED,
            'Log to file'
        );
        $this->addOption(
            'stage',
            null,
            Option::VALUE_REQUIRED,
            'Stage to deploy'
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
        $command = $input->getArgument('command-to-run');
        $stage = $input->getOption('stage');
        $roles = $input->getOption('roles');
        $hosts = $input->getOption('hosts');

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

        $task = new Task($command, function () use ($command, $hosts) {
            $output = run($command);
            if (count($hosts) > 1) {
                writeln("[{{hostname}}] > $output");
            } else {
                write($output);
            }
        });

        foreach ($hosts as $host) {
            $task->run(new Context($host, $input, $output));
        }

        return 0;
    }
}
