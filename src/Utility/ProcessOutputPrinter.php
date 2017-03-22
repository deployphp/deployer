<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

trait ProcessOutputPrinter
{
    /**
     * @param OutputInterface $output
     * @param string $hostname for debugging
     * @return \Closure
     */
    protected function callback(OutputInterface $output, string $hostname)
    {
        return function ($type, $buffer) use ($output, $hostname) {
            if ($output->isDebug()) {
                foreach (explode("\n", rtrim($buffer)) as $line) {
                    $this->writeln($output, $type, $hostname, $line);
                }
            }
        };
    }

    /**
     * @param OutputInterface $output
     * @param int $type Process::OUT or Process::ERR
     * @param string $hostname for debugging
     * @param string $line to print
     */
    protected function writeln(OutputInterface $output, $type, $hostname, $line)
    {
        $line = $this->filterOutput($line);

        // Omit empty lines
        if (empty($line)) {
            return;
        }

        if ($output->isDecorated()) {
            if ($type === Process::ERR) {
                $line = "[$hostname] \033[0;31m<\e[0m $line";
            } else {
                $line = "[$hostname] \033[0;90m< $line\033[0m";
            }
        } else {
            $line = "[$hostname] < $line";
        }

        $output->writeln($line, OutputInterface::OUTPUT_RAW);
    }

    /**
     * This filtering used only in Ssh\Client, but for simplify putted here.
     *
     * @param string $output
     * @return string
     */
    protected function filterOutput($output)
    {
        return preg_replace('/\[exit_code:(.*?)\]/', '', $output);
    }
}
