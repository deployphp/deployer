<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ssh;

use Deployer\Deployer;
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
            'timeout' => Deployer::getDefault('default_timeout', 300),
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($hostname, $command);

        $sshArguments = $host->getSshArguments();

        $become = $host->has('become') ? 'sudo -H -u ' . $host->get('become') : '';

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

        $shellCommand = $host->getShellCommand();

        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $ssh = "ssh $sshArguments $host $become \"$shellCommand; printf '[exit_code:%s]' $?;\"";
        } else {
            $ssh = "ssh $sshArguments $host $become '$shellCommand; printf \"[exit_code:%s]\" $?;'";
        }

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

            $output = $this->exec("ssh -N $sshArguments $host");

            if ($this->output->isVeryVerbose()) {
                $this->pop->writeln(Process::OUT, $host->getHostname(), $output);
            }
        }

        return $sshArguments;
    }

    private function isMultiplexingInitialized(Host $host, Arguments $sshArguments)
    {
        $process = new Process("ssh -O check $sshArguments $host 2>&1");
        $process->run();
        return (bool)preg_match('/Master running/', $process->getOutput());
    }

    private function exec($command, &$exitCode = null)
    {
        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        // Don't read from stderr, there is a bug in OpenSSH_7.2p2 (stderr doesn't closed with ControlMaster)

        $process = proc_open($command, $descriptors, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
        } else {
            $output = 'proc_open failure';
            $exitCode = 1;
        }
        return $output;
    }
}
