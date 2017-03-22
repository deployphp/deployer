<?php
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
    private $hostname;
    private $command;
    private $exitCode;
    private $output;
    private $errorOutput;

    /**
     * RuntimeException constructor.
     * @param string $hostname
     * @param int $command
     * @param $exitCode
     * @param $output
     * @param $errorOutput
     */
    public function __construct($hostname, $command, $exitCode, $output, $errorOutput)
    {
        $this->hostname = $hostname;
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;

        $message = sprintf(
            'The command "%s" failed.' .
            "\n\nExit Code: %s (%s)\n\nHost Name: %s",
            $command,
            $exitCode,
            $this->getExitCodeText($exitCode),
            $hostname
        );

        $message .= sprintf(
            "\n\n================\n%s",
            $errorOutput
        );

        parent::__construct($message, $exitCode);
    }

    private function getExitCodeText($exitCode)
    {
        return isset(Process::$exitCodes[$exitCode]) ? Process::$exitCodes[$exitCode] : 'Unknown error';
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @return int
     */
    public function getCommand(): int
    {
        return $this->command;
    }

    /**
     * @return \Exception|null
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return mixed
     */
    public function getErrorOutput()
    {
        return $this->errorOutput;
    }
}
