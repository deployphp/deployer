<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use function Deployer\hostnameTag;
use Deployer\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessOutputPrinter
{
    private $output;
    private $logger;

    public function __construct(OutputInterface $output, Logger $logger)
    {
        $this->output = $output;
        $this->logger = $logger;
    }

    /**
     * Returns a callable for use with the symfony Process->run($callable) method.
     *
     * @param string $hostname
     * @return callable A function expecting a int $type (e.g. Process::OUT or Process::ERR) and string $buffer parameters.
     */
    public function callback(string $hostname)
    {
        return function ($type, $buffer) use ($hostname) {
            $this->printBuffer($type, $hostname, $buffer);
        };
    }

    /**
     * @param string $type Process::OUT or Process::ERR
     * @param string $hostname
     * @param string $buffer
     */
    public function printBuffer($type, $hostname, $buffer)
    {
        foreach (explode("\n", rtrim($buffer)) as $line) {
            $this->writeln($type, $hostname, $line);
        }
    }

    public function command(string $hostname, string $command)
    {
        $this->logger->log("[$hostname] run $command");
        $this->output->writeln(hostnameTag($hostname) . "<fg=cyan>run</> $command");
    }

    /**
     * @param string $type Process::OUT or Process::ERR
     * @param string $hostname
     * @param string $line
     */
    public function writeln($type, $hostname, $line)
    {
        $line = $this->filterOutput($line);

        // Omit empty lines
        if (empty($line)) {
            return;
        }

        if ($type === Process::ERR) {
            $this->logger->log("[$hostname] [error] $line");
        } else {
            $this->logger->log("[$hostname] $line");
        }

        $prefix = hostnameTag($hostname);
        if ($type === Process::ERR) {
            $line = "$prefix<fg=red>err</> $line";
        } else {
            $line = "$prefix$line";
        }

        $this->output->writeln($line);
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
