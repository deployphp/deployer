<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Deployer;
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

    public function run($hostname, string $command, array $config = [])
    {
        $defaults = [
            'timeout' => Deployer::getDefault('default_timeout', 300),
            'tty' => false,
        ];
        $config = array_merge($defaults, $config);

        $this->pop->command($hostname, $command);

        $process = new Process($command);
        $process
            ->setTimeout($config['timeout'])
            ->setTty($config['tty'])
            ->mustRun($this->pop->callback($hostname));

        return $process->getOutput();
    }
}
