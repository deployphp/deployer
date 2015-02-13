<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\Output\OutputWatcher;
use Deployer\Task\NonFatalException;
use Symfony\Component\Console\Output\OutputInterface;

class Informer
{
    /**
     * @var \Deployer\Console\Output\OutputWatcher
     */
    private $output;

    /**
     * @param \Deployer\Console\Output\OutputWatcher $output
     */
    public function __construct(OutputWatcher $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $taskName
     */
    public function startTask($taskName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL) {
                $this->output->write("  ");
            } else {
                $this->output->write("➤ ");
            }

            $this->output->writeln("Executing task $taskName");

            $this->output->setWasWritten(false);
        }
    }

    /**
     * Print task was ok.
     */
    public function endTask($success = true)
    {
        if ($success) {
            $message = "<info>✔</info>";
            $append = 'OK';
        } else {
            $message = "<fg=red>✘</fg=red>";
            $append = 'Error';
        }

        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && !$this->output->getWasWritten()) {
            $this->output->write("\033[k\033[1A{$message}\n");
        } else {
            $this->output->writeln("{$message} {$append}");
        }
    }

    /**
     * @param string $serverName
     */
    public function onServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("⤷ on [$serverName]");
        }
    }

    /**
     * @param string $serverName
     */
    public function endOnServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("<info>⤶</info> done on [$serverName]");
        }
    }

    /**
     * Print error.
     *
     * @param  \Excetion|null $exception
     */
    public function taskError(\Exception $exception = null)
    {
        if ($exception instanceof NonFatalException) {
            $message = sprintf('Error: %s', $exception->getMessage());
        } else {
            $message = 'Some errors occurred!';
        }

        $this->output->writeln("<fg=red>✘</fg=red> <options=underscore>{$message}</options=underscore>");
    }
}
