<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ssh;

use Deployer\Exception\InitializationException;
use Deployer\Exception\RuntimeException;
use Deployer\Host\Host;
use Deployer\Utility\ProcessOutputPrinter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Client
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProcessOutputPrinter
     */
    private $pop;

    /**
     * @var bool
     */
    private $multiplexing;

    public function __construct(OutputInterface $output, ProcessOutputPrinter $pop, bool $multiplexing)
    {
        $this->output = $output;
        $this->pop = $pop;
        $this->multiplexing = $multiplexing;
    }

    /**
     * @param Host $host
     * @param string $command
     * @param array $config
     * @return string
     * @throws RuntimeException
     */
    public function run(Host $host, string $command, array $config = [])
    {
        $hostname = $host->getHostname();
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($hostname, $command);

        $sshArguments = $host->getSshArguments();
        $become = $host->has('become') ? 'sudo -u ' . $host->get('become') : '';

        // When tty need to be allocated, don't use multiplexing,
        // and pass command without bash allocation on remote host.
        if ($config['tty']) {
            $this->output->write(''); // Notify OutputWatcher
            $sshArguments = $sshArguments->withFlag('-tt');
            $command = escapeshellarg($command);

            $ssh = "ssh $sshArguments $host $command";
            $process = new Process($ssh);
            $process
                ->setTimeout($config['timeout'])
                ->setTty(true)
                ->mustRun();

            return $process->getOutput();
        }

        if ($host->isMultiplexing() === null ? $this->multiplexing : $host->isMultiplexing()) {
            $sshArguments = $this->initMultiplexing($host);
        }

        $ssh = "ssh $sshArguments $host $become 'bash -s; printf \"[exit_code:%s]\" $?;'";

        $process = new Process($ssh);
        $process
            ->setInput($command)
            ->setTimeout($config['timeout']);

        $process->run($this->pop->callback($hostname));

        $output = $this->pop->filterOutput($process->getOutput());
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

    private function parseExitStatus(Process $process)
    {
        $output = $process->getOutput();
        preg_match('/\[exit_code:(.*?)\]/', $output, $match);

        if (!isset($match[1])) {
            return -1;
        }

        $exitCode = (int)$match[1];
        return $exitCode;
    }

    private function initMultiplexing(Host $host)
    {
        $sshArguments = $host->getSshArguments()->withMultiplexing($host);

        if (!$this->isMultiplexingInitialized($host, $sshArguments)) {
            if ($this->output->isVeryVerbose()) {
                $this->pop->writeln(Process::OUT, $host->getHostname(), 'ssh multiplexing initialization');
            }

            // Open master connection explicit,
            // ControlMaster=auto could not working
            $process = new Process("ssh -M $sshArguments $host");
            $process->start();

            $attempts = 0;
            while (!$this->isMultiplexingInitialized($host, $sshArguments)) {
                if ($attempts++ > 30) {
                    throw new InitializationException('Wait time exceeded for ssh multiplexing initialization');
                }

                if (!$process->isRunning()) {
                    throw new InitializationException('Failed to initialize ssh multiplexing');
                }

                // Delay to check again if the connection is established
                sleep(1);
            }
        }

        return $sshArguments;
    }

    private function isMultiplexingInitialized(Host $host, Arguments $sshArguments)
    {
        $process = new Process("ssh -O check $sshArguments $host 2>&1");
        $process->run();

        return (bool) preg_match('/Master running/', $process->getOutput());
    }
}
