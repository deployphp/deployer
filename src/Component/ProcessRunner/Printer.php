<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\ProcessRunner;

use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function Deployer\hostTag;

class Printer
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function command(Host $host, string $command)
    {
        // -v for run command
        if ($this->output->isVerbose()) {
            $this->output->writeln("[{$host->getTag()}] <fg=green;options=bold>run</> $command");
        }
    }

    /**
     * Returns a callable for use with the symfony Process->run($callable) method.
     *
     * @param Host $host
     * @return callable A function expecting a int $type (e.g. Process::OUT or Process::ERR) and string $buffer parameters.
     */
    public function callback(Host $host)
    {
        return function ($type, $buffer) use ($host) {
            if ($this->output->isVerbose()) {
                $this->printBuffer($type, $host, $buffer);
            }
        };
    }

    /**
     * @param string $type Process::OUT or Process::ERR
     * @param Host $host
     * @param string $buffer
     */
    public function printBuffer(string $type, Host $host, string $buffer)
    {
        foreach (explode("\n", rtrim($buffer)) as $line) {
            $this->writeln($type, $host, $line);
        }
    }

    /**
     * @param string $type Process::OUT or Process::ERR
     * @param Host $host
     * @param string $line
     */
    public function writeln(string $type, Host $host, string $line)
    {
        $line = self::filterOutput($line);

        // Omit empty lines
        if (empty($line)) {
            return;
        }

        if ($type === Process::ERR) {
            $line = "[{$host->getTag()}] <fg=red>err</> $line";
        } else {
            $line = "[{$host->getTag()}] $line";
        }

        $this->output->writeln($line);
    }

    /**
     * This filtering used only in Ssh\Client, but for simplify putted here.
     *
     * @param string $output
     * @return string
     */
    public static function filterOutput($output)
    {
        return preg_replace('/\[exit_code:(.*?)]/', '', $output);
    }
}
