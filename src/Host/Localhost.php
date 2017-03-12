<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Localhost
{
    use ConfigurationAccessor;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return 'localhost';
    }

    public function exec(OutputInterface $output, string $command, array $config = [])
    {
        $hostname = $this->getHostname();
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);


        $process = new Process($command);
        $process
            ->setTimeout($config['timeout'])
            ->setTty($config['tty']);

        $callback = function ($type, $buffer) use ($output, $hostname) {
            if ($output->isDebug()) {
                foreach (explode("\n", rtrim($buffer)) as $line) {
                    if ($output->isDecorated()) {
                        if ($type === Process::ERR) {
                            $line = "[$hostname] \033[0;31m< $line\033[0m";
                        } else {
                            $line = "[$hostname] \033[1;30m< $line\033[0m";
                        }
                    } else {
                        $line = "[$hostname] < $line";
                    }

                    $output->writeln($line, OutputInterface::OUTPUT_RAW);
                }
            }
        };

        $process->mustRun($callback);

        return $process->getOutput();
    }
}
