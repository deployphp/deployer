<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Exception;

use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class RuntimeException extends Exception
{
    /**
     * @var string
     */
    private $hostname;

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

        $message = sprintf(
            'The command "%s" failed.'.
            "\n\nExit Code: %d (%s)\n\nHost Name: %s".
            "\n\n================\n%s",
            $command,
            $exitCode,
            $this->getExitCodeText($exitCode),
            $hostname,
            strlen(trim($errorOutput)) !== 0 ? $errorOutput : $output
        );

        parent::__construct($message, $exitCode);
    }

    private function getExitCodeText(int $exitCode): string
    {
        return Process::$exitCodes[$exitCode] ?? 'Unknown error';
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

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }
}
