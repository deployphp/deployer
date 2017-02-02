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
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Application extends Console
{
    /**
     * Input definition for user specific arguments and options.
     *
     * @var InputDefinition
     */
    private $userDefinition;

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        $inputDefinition->addOption(
            new InputOption('--file', '-f', InputOption::VALUE_OPTIONAL, 'Specify Deployer file')
        );

        return $inputDefinition;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        if ($this->isPharArchive()) {
            $commands[] = $this->selfUpdateCommand();
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    private function selfUpdateCommand()
    {
        $selfUpdate = new PharUpdateCommand('self-update');
        $selfUpdate->setDescription('Updates deployer.phar to the latest version');
        $selfUpdate->setManifestUri('https://deployer.org/manifest.json');
        return $selfUpdate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        if ($this->isPharArchive()) {
            $helperSet->set(new PharUpdateHelper());
        }
        return $helperSet;
    }

    /**
     * @return InputDefinition
     */
    public function getUserDefinition()
    {
        if (null === $this->userDefinition) {
            $this->userDefinition = new InputDefinition();
        }

        return $this->userDefinition;
    }

    /**
     * Add user definition arguments and options to definition.
     */
    public function addUserArgumentsAndOptions()
    {
        $this->getDefinition()->addArguments($this->getUserDefinition()->getArguments());
        $this->getDefinition()->addOptions($this->getUserDefinition()->getOptions());
    }

    public function isPharArchive()
    {
        return 'phar:' === substr(__FILE__, 0, 5);
    }
}
