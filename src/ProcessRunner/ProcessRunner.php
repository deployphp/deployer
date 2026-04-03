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
use function Deployer\Support\replace_secrets;

class ProcessRunner
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function run(Host $host, string $command, RunParams $params): string
    {
        $this->logger->command($host, 'run', $command);

        if (!empty($params->env)) {
            $env = env_stringify($params->env);
            $command = "export $env; $command";
        }

        if (!empty($params->dotenv)) {
            $command = "source $params->dotenv; $command";
        }

        $process = Process::fromShellCommandline($params->shell)
            ->setInput(replace_secrets($command, $params->secrets))
            ->setTimeout($params->timeout)
            ->setIdleTimeout($params->idleTimeout)
            ->setWorkingDirectory($params->cwd ?? deployer_root());

        $callback = function ($type, $buffer) use ($params, $host) {
            $this->logger->print($host, $buffer, $params->forceOutput);
        };

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
        } catch (ProcessTimedOutException $exception) {
            throw new TimeoutException(
                $command,
                $exception->getExceededTimeout(),
            );
        }
    }
}
