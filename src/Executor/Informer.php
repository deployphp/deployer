<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Console\StateOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Informer
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
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

            if ($this->output instanceof StateOutput) {
                $this->output->setWasWritten(false);
            }
        }
    }

    /**
     * Print task was ok.
     */
    public function endTask()
    {
        if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_NORMAL && $this->output instanceof StateOutput && !$this->output->getWasWritten()) {
            $this->output->write("\033[k\033[1A<info>✔</info>\n");
        } else {
            $this->output->writeln("<info>✔</info> Ok");
        }
    }

    /**
     * @param string $serverName
     */
    public function onServer($serverName)
    {
        if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln("⤷ on server $serverName");
        }
    }
}
