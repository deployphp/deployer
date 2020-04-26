<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

use Symfony\Component\Process\Process;

class RunException extends Exception
{
    private $hostname;
    private $command;
    private $exitCode;
    private $output;
    private $errorOutput;

    public function __construct(
        string $hostname,
        string $command,
        int $exitCode,
        string $output,
        string $errorOutput
    ) {
        $this->hostname = $hostname;
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;

        $message = sprintf('The command "%s" failed.', $command);
        parent::__construct($message, $exitCode);
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getExitCodeText(): string
    {
        return Process::$exitCodes[$this->exitCode] ?? 'Unknown error';
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }
}
