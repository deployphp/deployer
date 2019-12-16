<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Symfony\Component\Process\Process;

class Rsync
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

        $escapedSource = escapeshellarg($source);
        $escapedDestination = escapeshellarg($destination);
        $rsync = "rsync -azP " . implode(' ', $config['options']) . " $escapedSource $escapedDestination";

        $this->pop->command($hostname, $rsync);

        if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($rsync);
        } else {
            $process = new Process($rsync);
        }
        $process
            ->setTimeout($config['timeout'])
            ->mustRun($this->pop->callback($hostname));
    }
}
