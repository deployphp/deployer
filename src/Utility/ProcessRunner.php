<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    use ProcessOutputPrinter;

    public function run(OutputInterface $output, $hostname, string $command, array $config = [])
    {
        $defaults = [
            'timeout' => 300,
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        if ($output->isVeryVerbose()) {
            $output->writeln("[$hostname] <fg=cyan>></fg=cyan> $command");
        }

        $process = new Process($command);
        $process
            ->setTimeout($config['timeout'])
            ->setTty($config['tty'])
            ->mustRun($this->callback($output, $hostname));

        return $process->getOutput();
    }
}
