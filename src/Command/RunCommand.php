<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface as Output;
use function Deployer\cd;
use function Deployer\get;
use function Deployer\has;
use function Deployer\run;
use function Deployer\test;

class RunCommand extends SelectCommand
{
    use CustomOption;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('run', $deployer);
        $this->setDescription('Run any arbitrary command on hosts');
    }

    protected function configure()
    {
        $this->addArgument(
            'command-to-run',
            InputArgument::REQUIRED,
            'Command to run on a remote host'
        );
        parent::configure();
        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Set configuration option'
        );
        $this->addOption(
            'timeout',
            't',
            Option::VALUE_REQUIRED,
            'Command timeout in seconds'
        );
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;

        $command = $input->getArgument('command-to-run') ?? '';
        $hosts = $this->selectHosts($input, $output);
        $this->applyOverrides($hosts, $input->getOption('option'));

        $task = new Task($command, function () use ($input, $command) {
            if (has('current_path')) {
                $path = get('current_path');
                if (test("[ -d $path ]")) {
                    cd($path);
                }
            }
            run($command, [
                'real_time_output' => true,
                'timeout' => intval($input->getOption('timeout')),
            ]);
        });

        foreach ($hosts as $host) {
            try {
                $task->run(new Context($host));
            } catch (\Throwable $exception) {
                $this->deployer->messenger->renderException($exception, $host);
            }
        }

        return 0;
    }
}
