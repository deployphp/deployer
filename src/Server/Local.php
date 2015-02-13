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
    const TIMEOUT = 300;

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
            ->setTimeout(self::TIMEOUT)
            ->setIdleTimeout(self::TIMEOUT)
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
