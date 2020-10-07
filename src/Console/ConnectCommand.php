<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConnectCommand extends Command
{
    use CustomOption;

    protected $deployer;

    public function __construct(Deployer $deployer)
    {
        parent::__construct('connect');
        $this->deployer = $deployer;
        $this->setHidden(true);
    }

    protected function configure()
    {
        $this->addArgument('connect-host', InputArgument::REQUIRED);
        $this->addArgument('_', InputArgument::IS_ARRAY);
        $this->addOption('decorated', null, Option::VALUE_NONE);
        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Set configuration option'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;
        $output->setDecorated($input->getOption('decorated'));
        if (!$output->isDecorated() && !defined('NO_ANSI')) {
            define('NO_ANSI', 'true');
        }

        $host = $this->deployer->hosts->get($input->getArgument('connect-host'));
        $this->applyOverrides([$host], $input->getOption('option'));

        try {
            $this->deployer->sshClient->connect($host);
        } catch (ProcessFailedException $exception) {
            $output->writeln($exception->getProcess()->getErrorOutput());
            return 1;
        }
        return 0;
    }
}
