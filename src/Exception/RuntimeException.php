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
            trim($errorOutput) ? $errorOutput : $output
        );

        parent::__construct($message, $exitCode);
    }

    private function getExitCodeText($exitCode)
    {
        return isset(Process::$exitCodes[$exitCode]) ? Process::$exitCodes[$exitCode] : 'Unknown error';
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getErrorOutput()
    {
        return $this->errorOutput;
    }
}
