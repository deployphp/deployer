<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Ssh;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use function Deployer\Support\parse_home_dir;

class Client
{
    private $output;
    private $pop;
    private $logger;

    public function __construct(OutputInterface $output, Printer $pop, Logger $logger)
    {
        $this->output = $output;
        $this->pop = $pop;
        $this->logger = $logger;
    }

    /**
     * @throws RunException|TimeoutException|Exception
     */
    public function run(Host $host, string $command, array $config = []): string
    {
        $connectionString = $host->getConnectionString();
        $defaults = [
            'timeout' => $host->get('default_timeout', 300),
            'idle_timeout' => null,
            'real_time_output' => false,
            'no_throw' => false,
        ];
        $config = array_merge($defaults, $config);

        if ($host->getSshMultiplexing()) {
            $this->initMultiplexing($host);
        }

        $shellId = bin2hex(random_bytes(10));
        $shellCommand = $host->getShell();
        if ($host->has('become')) {
            $shellCommand = "sudo -H -u {$host->get('become')} " . $shellCommand;
        }

        $ssh = array_merge(['ssh'], self::connectionOptionsArray($host), [$connectionString, ": $shellId; $shellCommand"]);

        // -vvv for ssh command
        if ($this->output->isDebug()) {
            $sshString = $ssh[0];
            for ($i = 1; $i < count($ssh); $i++) {
                $sshString .= ' ' . escapeshellarg((string)$ssh[$i]);
            }
            $this->output->writeln("[$host] $sshString");
        }

        $this->pop->command($host, 'run', $command);
        $this->logger->log("[{$host->getAlias()}] run $command");

        $command = str_replace('%secret%', $config['secret'] ?? '', $command);
        $command = str_replace('%sudo_pass%', $config['sudo_pass'] ?? '', $command);

        $process = new Process($ssh);
        $process
            ->setInput("( $command ); printf '[exit_code:%s]' $?;")
            ->setTimeout($config['timeout'])
            ->setIdleTimeout($config['idle_timeout']);

        $callback = function ($type, $buffer) use ($config, $host) {
            $this->logger->printBuffer($host, $type, $buffer);
            $this->pop->callback($host, $config['real_time_output'])($type, $buffer);
        };

        try {
            $process->run($callback);
        } catch (ProcessTimedOutException $exception) {
            // Let's try to kill all processes started by this command.
            $pid = $this->run($host, "ps x | grep $shellId | grep -v grep | awk '{print \$1}'");
            $this->run($host, "kill -9 -$pid"); // Minus before pid means all processes in this group.
            throw new TimeoutException(
                $command,
                $exception->getExceededTimeout()
            );
        }

        $output = $this->pop->filterOutput($process->getOutput());
        $exitCode = $this->parseExitStatus($process);

        if ($exitCode !== 0 && !$config['no_throw']) {
            throw new RunException(
                $host,
                $command,
                $exitCode,
                $output,
                $process->getErrorOutput()
            );
        }

        return $output;
    }

    private function parseExitStatus(Process $process): int
    {
        preg_match('/\[exit_code:(\d*)]/', $process->getOutput(), $match);
        return (int)($match[1] ?? -1);
    }

    public function connect(Host $host)
    {
        if ($host->getSshMultiplexing()) {
            $this->initMultiplexing($host);
        }
    }

    private function initMultiplexing(Host $host): void
    {
        $options = self::connectionOptionsArray($host);

        if (!$this->isMasterRunning($host, $options)) {
            $connectionString = $host->getConnectionString();
            $command = array_merge(['ssh', '-N'], $options, [$connectionString]);

            if ($this->output->isDebug()) {
                $this->pop->writeln(Process::OUT, $host, '<info>ssh: multiplexing initialization</info>');
                $this->pop->writeln(Process::OUT, $host, join(' ', $command));
            }

            $process = new Process($command);
            $process->setTimeout(30); // Connection timeout (time needed to establish ssh multiplexing)

            try {
                $process->mustRun();
            } catch (ProcessTimedOutException $exception) {
                // Timeout fired: maybe there is no connection,
                // or maybe another process established master connection.
                // Let's try proceed anyway.
            }

            $output = $process->getOutput();

            if ($this->output->isDebug()) {
                $this->pop->printBuffer(Process::OUT, $host, $output);
            }
        }
    }

    private function isMasterRunning(Host $host, array $options): bool
    {
        $command = array_merge(['ssh', '-O', 'check'], $options, ['echo']);
        if ($this->output->isDebug()) {
            $this->pop->writeln(Process::OUT, $host, '<info>ssh: is master running?</info>');
            $this->pop->printBuffer(Process::OUT, $host, join(' ', $command));
        }

        $process = new Process($command);
        $process->run();
        $output = $process->getErrorOutput();

        if ($this->output->isDebug()) {
            $this->pop->printBuffer(Process::OUT, $host, $output);
        }
        return (bool)preg_match('/Master running/', $output);
    }

    public static function connectionOptionsString(Host $host): string
    {
        return implode(' ', array_map('escapeshellarg', self::connectionOptionsArray($host)));
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public static function connectionOptionsArray(Host $host): array
    {
        $options = [];

        if ($host->has('ssh_arguments')) {
            foreach ($host->getSshArguments() as $arg) {
                $options = array_merge($options, explode(' ', $arg));
            }
        }

        if ($host->has('port')) {
            $options = array_merge($options, ['-p', $host->getPort()]);
        }

        if ($host->has('config_file')) {
            $options = array_merge($options, ['-F', parse_home_dir($host->getConfigFile())]);
        }

        if ($host->has('identity_file')) {
            $options = array_merge($options, ['-i', parse_home_dir($host->getIdentityFile())]);
        }

        if ($host->has('forward_agent') && $host->getForwardAgent()) {
            $options = array_merge($options, ['-A']);
        }

        if ($host->has('ssh_multiplexing') && $host->getSshMultiplexing()) {
            $options = array_merge($options, [
                '-o', 'ControlMaster=auto',
                '-o', 'ControlPersist=60',
                '-o', 'ControlPath=' . self::generateControlPath($host),
            ]);
        }

        return $options;
    }

    /**
     * Return SSH multiplexing control path
     *
     * When ControlPath is longer than 104 chars we can get:
     *
     *     SSH Error: unix_listener: too long for Unix domain socket
     *
     * So try to get as descriptive path as possible.
     * %C is for creating hash out of connection attributes.
     */
    private static function generateControlPath(Host $host): string
    {
        // In case of CI environment, lets use shared memory.
        if (getenv('CI') && is_writable('/dev/shm')) {
            return '/dev/shm/%C';
        }

        $connectionHashLength = 17; // Length of connection hash that OpenSSH appends to controlpath
        $unixMaxPath = 104; // Theoretical max limit for path length
        $homeDir = parse_home_dir('~');
        $port = empty($host->get('port', '')) ? '' : ':' . $host->getPort();
        $connectionData = "{$host->getConnectionString()}$port";

        $tryLongestPossible = 0;
        $controlPath = '';
        do {
            switch ($tryLongestPossible) {
                case 1:
                    $controlPath = "$homeDir/.ssh/deployer_%C";
                    break;
                case 2:
                    $controlPath = "$homeDir/.ssh/" . hash("crc32", $connectionData);
                    break;
                case 3:
                    throw new Exception("The multiplexing control path is too long. Control path is: $controlPath");
                default:
                    $controlPath = "$homeDir/.ssh/deployer_$connectionData";
            }
            $tryLongestPossible++;
        } while (strlen($controlPath) + $connectionHashLength >= $unixMaxPath); // Unix socket max length

        return $controlPath;
    }
}
