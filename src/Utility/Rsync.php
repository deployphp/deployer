<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Rsync
{
    use ProcessOutputPrinter;

    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Start rsync process
     *
     * @param $hostname
     * @param $source
     * @param $destination
     * @param array $config
     */
    public function call($hostname, $source, $destination, array $config = [])
    {
        $defaults = [
            'timeout' => null,
            'options' => [],
        ];
        $config = array_merge($defaults, $config);

        $rsync = "rsync -azP " . implode(' ', $config['options']) . " $source $destination";

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln("[$hostname] <fg=cyan>></fg=cyan> $rsync");
        }

        $process = new Process($rsync);
        $process
            ->setTimeout($config['timeout'])
            ->mustRun($this->callback($this->output, $hostname));
    }
}
