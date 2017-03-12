<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ssh;

use Deployer\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Client
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Client constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }


    public function run($host, $command, $options = [])
    {
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $options = array_merge($defaults, $options);

        $hostname = $host;
        $ssh = "ssh $hostname 'bash -s; printf \"[exit_code:%s]\" $?;'";

        $process = new Process($ssh);
        $process
            ->setInput($command)
            ->setTimeout($options['timeout'])
            ->setTty($options['tty']);

        $callback = function ($type, $buffer) use ($hostname) {
            if ($this->output->isDebug()) {
                foreach (explode("\n", rtrim($buffer)) as $line) {
                    $this->writeln($type, $hostname, $line);
                }
            }
        };

        $process->run($callback);
        $output = $this->filterOutput($process->getOutput());

        $exitCode = $this->parseExitStatus($process);
        if ($exitCode !== 0) {
            throw new RuntimeException(
                $hostname,
                $command,
                $exitCode,
                $output,
                $process->getErrorOutput()
            );
        }

        return $output;
    }

    private function writeln($type, $hostname, $output)
    {
        $output = $this->filterOutput($output);

        // Omit empty lines
        if (empty($output)) {
            return;
        }

        if ($this->output->isDecorated()) {
            if ($type === Process::ERR) {
                $output = "[$hostname] \033[0;31m< $output\033[0m";
            } else {
                $output = "[$hostname] \033[1;30m< $output\033[0m";
            }
        } else {
            $output = "[$hostname] < $output";
        }

        $this->output->writeln($output, OutputInterface::OUTPUT_RAW);
    }

    private function filterOutput($output)
    {
        return preg_replace('/\[exit_code:(.*?)\]$/', '', $output);
    }

    private function parseExitStatus(Process $process)
    {
        $output = $process->getOutput();
        preg_match('/\[exit_code:(.*?)\]$/', $output, $match);

        $exitCode = (int)$match[1];
        return $exitCode;
    }
}
