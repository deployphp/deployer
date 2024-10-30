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

use function Deployer\Support\env_stringify;

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

    public function run(Host $host, string $command, RunParams $params): string
    {
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

        if (!empty($params->cwd)) {
            $command = "cd $params->cwd && ($command)";
        }

        if (!empty($params->env)) {
            $env = env_stringify($params->env);
            $command = "export $env; $command";
        }

        if (!empty($params->secrets)) {
            foreach ($params->secrets as $key => $value) {
                $command = str_replace('%' . $key . '%', strval($value), $command);
            }
        }

        $this->pop->command($host, 'run', $command);
        $this->logger->log("[{$host->getAlias()}] run $command");


        $process = new Process($ssh);
        $process
            ->setInput($command)
            ->setTimeout($params->timeout)
            ->setIdleTimeout($params->idleTimeout);

        $callback = function ($type, $buffer) use ($params, $host) {
            $this->logger->printBuffer($host, $type, $buffer);
            $this->pop->callback($host, $params->forceOutput)($type, $buffer);
        };

        try {
            $process->run($callback);
        } catch (ProcessTimedOutException $exception) {
            // Let's try to kill all processes started by this command.
            $pid = $this->run($host, "ps x | grep $shellId | grep -v grep | awk '{print \$1}'", $params->with(timeout: 10));
            // Minus before pid means all processes in this group.
            $this->run($host, "kill -9 -$pid", $params->with(timeout: 20));
            throw new TimeoutException(
                $command,
                $exception->getExceededTimeout(),
            );
        }

        $output = $process->getOutput();
        $exitCode = $process->getExitCode();

        if ($exitCode !== 0 && !$params->nothrow) {
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
