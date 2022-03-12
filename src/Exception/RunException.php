<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

use Deployer\Host\Host;
use Symfony\Component\Process\Process;

class RunException extends Exception
{
    /**
     * @var Host
     */
    private $host;
    /**
     * @var string
     */
    private $command;
    /**
     * @var int
     */
    private $exitCode;
    /**
     * @var string
     */
    private $output;
    /**
     * @var string
     */
    private $errorOutput;

    public function __construct(
        Host $host,
        string $command,
        int $exitCode,
        string $output,
        string $errorOutput
    ) {
        $this->host = $host;
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;

        $message = sprintf('The command "%s" failed.', $command);
        parent::__construct($message, $exitCode);
    }

    public function getHost(): Host
    {
        return $this->host;
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
