<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Symfony\Component\Process\Process;

class Local implements ServerInterface
{
    /**
     * Number seconds for waiting a command is executed on server
     *
     * @var int
     */
    private $timeout;

    /**
     * @param int $timeout
     */
    public function __construct($timeout = 300)
    {
        $this->timeout = (int) $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // We do not need to connect to local server.
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $process = new Process($command);
        $process
            ->setTimeout($this->timeout)
            ->setIdleTimeout($this->timeout)
            ->mustRun();

        return $process->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        copy($local, $remote);
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        copy($remote, $local);
    }
}
