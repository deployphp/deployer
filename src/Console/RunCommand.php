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

class RunCommand extends SelectCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('run', $deployer);
        $this->setDescription('Run any arbitrary command on hosts');
    }

    protected function configure()
    {
        parent::configure();
        $this->addArgument(
            'command-to-run',
            InputArgument::IS_ARRAY,
            'Command to run'
        );
    }

    protected function execute(Input $input, Output $output)
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;

        if ($output->getVerbosity() === Output::VERBOSITY_NORMAL) {
            $output->setVerbosity(Output::VERBOSITY_VERBOSE);
        }

        $command = implode(' ', $input->getArgument('command-to-run') ?? '');
        $hosts = $this->selectHosts($input, $output);

        $task = new Task($command, function () use ($command, $hosts) {
            run($command);
        });

        foreach ($hosts as $host) {
            try {
                $task->run(new Context($host, $input, $output));
            } catch (\Throwable $exception) {
                $this->deployer->messenger->renderException($exception, $host);
            }
        }

        return 0;
    }
}
