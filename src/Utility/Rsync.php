<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Rsync
{
    private $pop;
    private $output;

    public function __construct(Printer $pop, OutputInterface $output)
    {
        $this->pop = $pop;
        $this->output = $output;
    }

    /*
     * Start rsync process.
     */
    public function call(Host $host, string $source, string $destination, array $config = [])
    {
        $defaults = [
            'timeout' => null,
            'options' => [],
        ];
        $config = array_merge($defaults, $config);

        $options = $config['options'] ?? [];

        $sshArguments = $host->getSshArguments()->getCliArguments();
        if ($sshArguments !== '') {
            $options[] = "-e 'ssh $sshArguments'";
        }

        if ($host->has("become")) {
            $options[] = "--rsync-path='sudo -H -u {$host->get('become')} rsync'";
        }

        $command = sprintf(
            "rsync -azP %s %s %s",
            implode(' ', $options),
            escapeshellarg($source),
            escapeshellarg($destination)
        );
        $this->pop->command($host, $command);

        $progressBar = null;
        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
            $progressBar = new ProgressBar($this->output);
            $progressBar->setBarCharacter('<info>≡</info>');
            $progressBar->setProgressCharacter('>');
            $progressBar->setEmptyBarCharacter('-');
        }

        $callback = function ($type, $buffer) use ($host, $progressBar) {
            if ($progressBar) {
                foreach (explode("\n", $buffer) as $line) {
                    if (preg_match('/(to-chk|to-check)=(\d+?)\/(\d+)/', $line, $match)) {
                        $max = intval($match[3]);
                        $step = $max - intval($match[2]);
                        $progressBar->setMaxSteps($max);
                        $progressBar->setFormat("[{$host->getTag()}] %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%");
                        $progressBar->setProgress($step);
                    }
                }
                return;
            }
            if ($this->output->isVerbose()) {
                $this->pop->printBuffer($type, $host, $buffer);
            }
        };

        $process = Process::fromShellCommandline($command)
            ->setTimeout($config['timeout']);
        try {
            $process->mustRun($callback);
        } catch (ProcessFailedException $exception) {
            throw new RunException(
                $host,
                $command,
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            );
        } finally {
            if ($progressBar) {
                $progressBar->clear();
            }
        }
    }
}
