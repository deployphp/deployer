<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Console;

use Deployer\Component\PharUpdate\Console\Command as PharUpdateCommand;
use Deployer\Component\PharUpdate\Console\Helper as PharUpdateHelper;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends Console
{
    /**
     * Input definition for user specific arguments and options.
     *
     * @var InputDefinition
     */
    private $userDefinition;
    private $after;

    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'Specify Deployer file')
        );

        return $inputDefinition;
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        if ($this->isPharArchive()) {
            $commands[] = $this->selfUpdateCommand();
        }

        return $commands;
    }

    private function selfUpdateCommand()
    {
        $selfUpdate = new PharUpdateCommand('self-update');
        $selfUpdate->setDescription('Updates deployer.phar to the latest version');
        $selfUpdate->setManifestUri('https://deployer.org/manifest.json');
        return $selfUpdate;
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        if ($this->isPharArchive()) {
            $helperSet->set(new PharUpdateHelper());
        }
        return $helperSet;
    }

    public function getUserDefinition()
    {
        if (null === $this->userDefinition) {
            $this->userDefinition = new InputDefinition();
        }

        return $this->userDefinition;
    }

    public function addUserArgumentsAndOptions()
    {
        $this->getDefinition()->addArguments($this->getUserDefinition()->getArguments());
        $this->getDefinition()->addOptions($this->getUserDefinition()->getOptions());
    }

    public function isPharArchive()
    {
        return 'phar:' === substr(__FILE__, 0, 5);
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        $exception = null;
        $exitCode = 0;

        try {
            $exitCode = parent::doRunCommand($command, $input, $output);
        } catch (\Throwable $x) {
            $exception = $x;
        }

        if (!empty($this->after)) {
            call_user_func($this->after, new CommandEvent($command, $input, $output, $exception, $exitCode));
        }

        if ($exception !== null) {
            throw $exception;
        }

        return $exitCode;
    }

    public function afterRun($callable)
    {
        $this->after = $callable;
    }
}
