<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\ProcessRunner;

use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Host\Host;
use Deployer\Logger\Logger;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @var Printer
     */
    private $pop;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Printer $pop, Logger $logger)
    {
        $this->pop = $pop;
        $this->logger = $logger;
    }

    /**
     * Runs a command, consider deployer global configs (timeout,...)
     *
     * @throws RunException
     */
    public function run(Host $host, string $command, array $config = []): string
    {
        $defaults = [
            'timeout' => $host->get('default_timeout', 300),
            'idle_timeout' => null,
            'cwd' => getenv('DEPLOYER_ROOT') !== false ? getenv('DEPLOYER_ROOT') : (defined('DEPLOYER_DEPLOY_FILE') ? dirname(DEPLOYER_DEPLOY_FILE) : null),
            'real_time_output' => false,
            'shell' => 'bash -s',
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($host, 'run', $command);

        $terminalOutput = $this->pop->callback($host, $config['real_time_output']);
        $callback = function ($type, $buffer) use ($host, $terminalOutput) {
            $this->logger->printBuffer($host, $type, $buffer);
            $terminalOutput($type, $buffer);
        };

        $command = str_replace('%secret%', $config['secret'] ?? '', $command);
        $command = str_replace('%sudo_pass%', $config['sudo_pass'] ?? '', $command);

        $process = Process::fromShellCommandline($config['shell'])
            ->setInput($command)
            ->setTimeout($config['timeout'])
            ->setIdleTimeout($config['idle_timeout']);

        if ($config['cwd'] !== null) {
            $process->setWorkingDirectory($config['cwd']);
        }

        try {
            $process->mustRun($callback);
            return $process->getOutput();
        } catch (ProcessFailedException $exception) {
            throw new RunException(
                $host,
                $command,
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            );
        } catch (ProcessTimedOutException $exception) { // @phpstan-ignore-line can be thrown but is absent from the phpdoc
            throw new TimeoutException(
                $command,
                $exception->getExceededTimeout()
            );
        }
    }
}
