<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Deployer;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @var ProcessOutputPrinter
     */
    private $pop;

    public function __construct(ProcessOutputPrinter $pop)
    {
        $this->pop = $pop;
    }

    /**
     * Runs a command, consider deployer global configs (timeout,...)
     *
     * @param string $hostname
     * @param string $command
     * @param array $config
     *
     * @return string
     *
     * @throws ProcessFailedException When the process does not return a 0 exit code.
     */
    public function run($hostname, string $command, array $config = [])
    {
        $defaults = [
            'timeout' => Deployer::getDefault('default_timeout', 300),
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($hostname, $command);

        if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command);
        }

        $process
            ->setTimeout($config['timeout'])
            ->setTty($config['tty'])
            ->mustRun($this->pop->callback($hostname));

        return $process->getOutput();
    }
}
