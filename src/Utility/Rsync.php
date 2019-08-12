<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Utility;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Host\Host;
use Symfony\Component\Process\Process;

class Rsync
{
    /**
     * @var Printer
     */
    private $pop;

    public function __construct(Printer $pop)
    {
        $this->pop = $pop;
    }

    /**
     * Start rsync process
     *
     * @param Host $host
     * @param string $source
     * @param string $destination
     * @param array $config
     */
    public function call(Host $host, string $source, string $destination, array $config = [])
    {
        $defaults = [
            'timeout' => null,
            'options' => [],
        ];
        $config = array_merge($defaults, $config);

        $escapedSource = escapeshellarg($source);
        $escapedDestination = escapeshellarg($destination);
        $rsync = "rsync -azP " . implode(' ', $config['options']) . " $escapedSource $escapedDestination";

        $this->pop->command($host, $rsync);

        $process = Process::fromShellCommandline($rsync);
        $process
            ->setTimeout($config['timeout'])
            ->mustRun($this->pop->callback($host));
    }
}
