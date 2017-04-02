<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Ssh;

use Deployer\Exception\Exception;
use Deployer\Exception\RuntimeException;
use Deployer\Host\Host;
use Deployer\Utility\ProcessOutputPrinter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Client
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProcessOutputPrinter
     */
    private $pop;

    /**
     * @var bool
     */
    private $multiplexing;

    public function __construct(OutputInterface $output, ProcessOutputPrinter $pop, bool $multiplexing)
    {
        $this->output = $output;
        $this->pop = $pop;
        $this->multiplexing = $multiplexing;
    }

    /**
     * @param Host $host
     * @param string $command
     * @param array $config
     * @return string
     * @throws RuntimeException
     */
    public function run(Host $host, string $command, array $config = [])
    {
        $hostname = $host->getHostname();
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($hostname, $command);

        $options = $host->sshOptions();
        $become = $host->has('become') ? 'sudo -u ' . $host->get('become') : '';

        // When tty need to be allocated, don't use multiplexing,
        // and pass command without bash allocation on remote host.
        if ($config['tty']) {
            $this->output->write(''); // Notify OutputWatcher
            $options .= ' -tt';
            $command = escapeshellarg($command);

            $ssh = "ssh $options $host $command";
            $process = new Process($ssh);
            $process
                ->setTimeout($config['timeout'])
                ->setTty(true)
                ->mustRun();

            return $process->getOutput();
        }

        if ($host->isMultiplexing() === null ? $this->multiplexing : $host->isMultiplexing()) {
            $options = $this->initMultiplexing($host);
        }

        $ssh = "ssh $options $host $become 'bash -s; printf \"[exit_code:%s]\" $?;'";

        $process = new Process($ssh);
        $process
            ->setInput($command)
            ->setTimeout($config['timeout']);

        $process->run($this->pop->callback($hostname));

        $output = $this->pop->filterOutput($process->getOutput());
        $exitCode = $this->parseExitStatus($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                $hostname,
                $command,
                $exitCode,
                $output,
                $process->getErrorOutput()
            );
        }

        return $output;
    }

    private function parseExitStatus(Process $process)
    {
        $output = $process->getOutput();
        preg_match('/\[exit_code:(.*?)\]/', $output, $match);

        if (!isset($match[1])) {
            return -1;
        }

        $exitCode = (int)$match[1];
        return $exitCode;
    }

    /**
     * Init multiplexing by adding options for ssh command
     *
     * @param Host $host
     * @return string Host options
     */
    private function initMultiplexing(Host $host)
    {
        $options = $host->sshOptions();
        $controlPath = $this->generateControlPath($host);

        $options .= " -o ControlMaster=auto";
        $options .= " -o ControlPersist=60";
        $options .= " -o ControlPath=$controlPath";

        $process = new Process("ssh $options -O check -S $controlPath $host 2>&1");
        $process->run();

        if (!preg_match('/Master running/', $process->getOutput()) && $this->output->isVeryVerbose()) {
            $this->pop->writeln(Process::OUT, $host->getHostname(), 'ssh multiplexing initialization');
        }

        return $options;
    }

    /**
     * Return SSH multiplexing control path
     *
     * When ControlPath is longer than 104 chars we can get:
     *
     *     SSH Error: unix_listener: too long for Unix domain socket
     *
     * So try to get as descriptive path as possible.
     * %C is for creating hash out of connection attributes.
     *
     * @param Host $host
     * @return string ControlPath
     * @throws Exception
     */
    private function generateControlPath(Host $host)
    {
        $connectionData = "$host{$host->getPort()}";
        $tryLongestPossible = 0;
        $controlPath = '';
        do {
            switch ($tryLongestPossible) {
                case 1:
                    $controlPath = "~/.ssh/deployer_mux_$connectionData";
                    break;
                case 2:
                    $controlPath = "~/.ssh/deployer_mux_%C";
                    break;
                case 3:
                    $controlPath = "~/deployer_mux_$connectionData";
                    break;
                case 4:
                    $controlPath = "~/deployer_mux_%C";
                    break;
                case 5:
                    $controlPath = "~/mux_%C";
                    break;
                case 6:
                    throw new Exception("The multiplexing control path is too long. Control path is: $controlPath");
                default:
                    $controlPath = "~/.ssh/deployer_mux_$connectionData";
            }
            $tryLongestPossible++;
        } while (strlen($controlPath) > 104); // Unix socket max length

        return $controlPath;
    }
}
