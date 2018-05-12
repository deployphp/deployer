<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessOutputPrinter
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(OutputInterface $output, Logger $logger)
    {
        $this->output = $output;
        $this->logger = $logger;
    }

    /**
     * Returns a callable for use with the symfony Process->run($callable) method.
     *
     * @return callable A function expecting a int $type (e.g. Process::OUT or Process::ERR) and string $buffer parameters.
     */
    public function callback(string $hostname)
    {
        return function ($type, $buffer) use ($hostname) {
            foreach (explode("\n", rtrim($buffer)) as $line) {
                $this->writeln($type, $hostname, $line);
            }
        };
    }

    public function command(string $hostname, string $command)
    {
        $this->logger->log("[$hostname] > $command");

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("[$hostname] <fg=cyan>></fg=cyan> $command");
        }
    }

    /**
     * @param int $type Process::OUT or Process::ERR
     * @param string $hostname for debugging
     * @param string $line to print
     */
    public function writeln($type, $hostname, $line)
    {
        $line = $this->filterOutput($line);

        // Omit empty lines
        if (empty($line)) {
            return;
        }

        if ($type === Process::ERR) {
            $this->logger->log("[$hostname] < [error] $line");
        } else {
            $this->logger->log("[$hostname] < $line");
        }

        if ($this->output->isDecorated()) {
            if ($type === Process::ERR) {
                $line = "[$hostname] \033[0;31m<\e[0m $line";
            } else {
                $line = "[$hostname] \033[0;90m< $line\033[0m";
            }
        } else {
            $line = "[$hostname] < $line";
        }

        if ($this->output->isDebug()) {
            $this->output->writeln($line, OutputInterface::OUTPUT_RAW);
        }
    }

    /**
     * This filtering used only in Ssh\Client, but for simplify putted here.
     *
     * @param string $output
     * @return string
     */
    public function filterOutput($output)
    {
        return preg_replace('/\[exit_code:(.*?)\]/', '', $output);
    }
}
