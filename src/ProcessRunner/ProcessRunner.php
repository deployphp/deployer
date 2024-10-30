<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\ProcessRunner;

use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Deployer\Ssh\RunParams;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function Deployer\Support\deployer_root;
use function Deployer\Support\env_stringify;

class ProcessRunner
{
    private Printer $pop;
    private Logger $logger;

    public function __construct(Printer $pop, Logger $logger)
    {
        $this->pop = $pop;
        $this->logger = $logger;
    }

    public function run(Host $host, string $command, RunParams $params): string
    {
        $this->pop->command($host, 'run', $command);

        $terminalOutput = $this->pop->callback($host, $params->forceOutput);
        $callback = function ($type, $buffer) use ($host, $terminalOutput) {
            $this->logger->printBuffer($host, $type, $buffer);
            $terminalOutput($type, $buffer);
        };

        if (!empty($params->secrets)) {
            foreach ($params->secrets as $key => $value) {
                $command = str_replace('%' . $key . '%', $value, $command);
            }
        }

        if (!empty($params->env)) {
            $env = env_stringify($params->env);
            $command = "export $env; $command";
        }

        if (!empty($params->dotenv)) {
            $command = "source $params->dotenv; $command";
        }

        $process = Process::fromShellCommandline($params->shell)
            ->setInput($command)
            ->setTimeout($params->timeout)
            ->setIdleTimeout($params->idleTimeout)
            ->setWorkingDirectory($params->cwd ?? deployer_root());

        try {
            $process->mustRun($callback);
            return $process->getOutput();
        } catch (ProcessFailedException) {
            if ($params->nothrow) {
                return '';
            }
            throw new RunException(
                $host,
                $command,
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput(),
            );
        } catch (ProcessTimedOutException $exception) { // @phpstan-ignore-line PHPStan doesn't know about ProcessTimedOutException for some reason.
            throw new TimeoutException(
                $command,
                $exception->getExceededTimeout(),
            );
        }
    }
}
