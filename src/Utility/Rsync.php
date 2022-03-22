<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Component\Ssh\Client;
use Deployer\Exception\RunException;
use Deployer\Host\Host;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function Deployer\writeln;

class Rsync
{
    /**
     * @var Printer
     */
    private $pop;
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Printer $pop, OutputInterface $output)
    {
        $this->pop = $pop;
        $this->output = $output;
    }

    /**
     * Start rsync process.
     *
     * @param string|string[] $source
     * @phpstan-param array{flags?: string, options?: array, timeout?: int|null, progress_bar?: bool, display_stats?: bool} $config
     * @throws RunException
     */
    public function call(Host $host, $source, string $destination, array $config = []): void
    {
        $defaults = [
            'timeout' => null,
            'options' => [],
            'flags' => '-azP',
            'progress_bar' => true,
            'display_stats' => false
        ];
        $config = array_merge($defaults, $config);

        $options = $config['options'] ?? [];
        $flags = $config['flags'];
        $displayStats = $config['display_stats'] || in_array('--stats', $options, true);

        if ($displayStats && !in_array('--stats', $options, true)) {
            $options[] = '--stats';
        }

        $connectionOptions = $host->connectionOptionsString();
        if ($connectionOptions !== '') {
            $options = array_merge($options, ['-e', "ssh $connectionOptions"]);
        }
        if ($host->has("become")) {
            $options = array_merge($options, ['--rsync-path', "sudo -H -u {$host->get('become')} rsync"]);
        }
        if (!is_array($source)) {
            $source = [$source];
        }
        $command = array_merge(['rsync', $flags], $options, $source, [$destination]);

        $commandString = $command[0];
        for ($i = 1; $i < count($command); $i++) {
            $commandString .= ' ' . escapeshellarg($command[$i]);
        }
        if ($this->output->isVerbose()) {
            $this->output->writeln("[$host] $commandString");
        }

        $progressBar = null;
        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL && $config['progress_bar']) {
            $progressBar = new ProgressBar($this->output);
            $progressBar->setBarCharacter('<info>≡</info>');
            $progressBar->setProgressCharacter('>');
            $progressBar->setEmptyBarCharacter('-');
        }

        $fullOutput = '';

        $callback = function ($type, $buffer) use ($host, $progressBar, &$fullOutput) {
            $fullOutput .= $buffer;
            if ($progressBar) {
                foreach (explode("\n", $buffer) as $line) {
                    if (preg_match('/(to-chk|to-check)=(\d+?)\/(\d+)/', $line, $match)) {
                        $max = intval($match[3]);
                        $step = $max - intval($match[2]);
                        $progressBar->setMaxSteps($max);
                        $progressBar->setFormat("[$host] %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%");
                        $progressBar->setProgress($step);
                    }
                }
                return;
            }
            if ($this->output->isVerbose()) {
                $this->pop->printBuffer($type, $host, $buffer);
            }
        };

        $process = new Process($command);
        $process->setTimeout($config['timeout']);
        try {
            $process->mustRun($callback);

            if ($displayStats) {
                $stats = [];

                $statsStarted = false;
                foreach (explode("\n", $fullOutput) as $line) {
                    if (strpos($line, 'Number of files') === 0) {
                        $statsStarted = true;
                    }

                    if ($statsStarted) {
                        if (empty($line)) {
                            break;
                        }
                        $stats[] = $line;
                    }
                }

                writeln("Rsync operation stats\n" . '<comment>' . implode("\n", $stats) . '</comment>');
            }

        } catch (ProcessFailedException $exception) {
            throw new RunException(
                $host,
                $commandString,
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
