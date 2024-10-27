<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ssh;

use Deployer\ProcessRunner\Printer;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class SshClient
{
    private OutputInterface $output;
    private Printer $pop;
    private Logger $logger;

    public function __construct(OutputInterface $output, Printer $pop, Logger $logger)
    {
        $this->output = $output;
        $this->pop = $pop;
        $this->logger = $logger;
    }

    public function run(Host $host, string $command, array $config = []): string
    {
        $defaults = [
            'timeout' => $host->get('default_timeout', 300),
            'idle_timeout' => null,
            'real_time_output' => false,
            'no_throw' => false,
        ];
        $config = array_merge($defaults, $config);

        $shellId = 'id$' . bin2hex(random_bytes(10));
        $shellCommand = $host->getShell();
        if ($host->has('become') && !empty($host->get('become'))) {
            $shellCommand = "sudo -H -u {$host->get('become')} " . $shellCommand;
        }

        $ssh = array_merge(['ssh'], $host->connectionOptionsArray(), [$host->connectionString(), ": $shellId; $shellCommand"]);

        // -vvv for ssh command
        if ($this->output->isDebug()) {
            $sshString = $ssh[0];
            for ($i = 1; $i < count($ssh); $i++) {
                $sshString .= ' ' . escapeshellarg((string) $ssh[$i]);
            }
            $this->output->writeln("[$host] $sshString");
        }

        $this->pop->command($host, 'run', $command);
        $this->logger->log("[{$host->getAlias()}] run $command");

        $command = str_replace('%secret%', strval($config['secret'] ?? ''), $command);
        $command = str_replace('%sudo_pass%', strval($config['sudo_pass'] ?? ''), $command);

        $process = new Process($ssh);
        $process
            ->setInput($command)
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
                $exception->getExceededTimeout(),
            );
        }

        $output = $process->getOutput();
        $exitCode = $process->getExitCode();

        if ($exitCode !== 0 && !$config['no_throw']) {
            throw new RunException(
                $host,
                $command,
                $exitCode,
                $output,
                $process->getErrorOutput(),
            );
        }

        return $output;
    }
}
