<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Command;

use Deployer\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption as Option;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConnectCommand extends MainCommand
{
    public function __construct(Deployer $deployer)
    {
        parent::__construct('connect', null, $deployer);
        $this->setHidden(true);
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption('host', null, Option::VALUE_REQUIRED);
        $this->addOption('decorated', null, Option::VALUE_NONE);
        $this->addOption(
            'option',
            'o',
            Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY,
            'Set configuration option'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deployer->input = $input;
        $this->deployer->output = $output;
        $output->setDecorated($input->getOption('decorated'));
        if (!$output->isDecorated() && !defined('NO_ANSI')) {
            define('NO_ANSI', 'true');
        }

        $host = $this->deployer->hosts->get($input->getOption('host'));
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
