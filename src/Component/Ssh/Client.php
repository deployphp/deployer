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
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Printer
     */
    private $pop;

    /**
     * @var Logger
     */
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
        $defaults = [
            'timeout' => $host->get('default_timeout', 300),
            'idle_timeout' => null,
            'real_time_output' => false,
            'no_throw' => false,
        ];
        $config = array_merge($defaults, $config);

        $shellId = bin2hex(random_bytes(10));
        $shellCommand = $host->getShell();
        if ($host->has('become')) {
            $shellCommand = "sudo -H -u {$host->get('become')} " . $shellCommand;
        }

        $ssh = array_merge(['ssh'], $host->connectionOptionsArray(), [$host->connectionString(), ": $shellId; $shellCommand"]);

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

        $command = str_replace('%secret%', strval($config['secret'] ?? ''), $command);
        $command = str_replace('%sudo_pass%', strval($config['sudo_pass'] ?? ''), $command);

        $process = new Process($ssh);
        $process
            ->setInput("( $command ); printf '[exit_code:%s]' $?;")
            ->setTimeout((null === $config['timeout']) ? null : (float) $config['timeout'])
            ->setIdleTimeout((null === $config['idle_timeout']) ? null : (float) $config['idle_timeout']);

        $callback = function ($type, $buffer) use ($config, $host) {
            $this->logger->printBuffer($host, $type, $buffer);
            $this->pop->callback($host, boolval($config['real_time_output']))($type, $buffer);
        };

        try {
            $process->run($callback);
        } catch (ProcessTimedOutException $exception) {
            // Let's try to kill all processes started by this command.
            $pid = $this->run($host, "ps x | grep $shellId | grep -v grep | awk '{print \$1}'");
            // Minus before pid means all processes in this group.
            $this->run($host, "kill -9 -$pid");
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
}
