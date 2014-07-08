<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class DryRun extends AbstractServer
{
    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        writeln("[{$this->config->getHost()}] Connecting to server.");
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        writeln("[{$this->config->getHost()}] Run command: {$command}");
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        writeln("[{$this->config->getHost()}] Upload file {$local} to {$remote}");
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        writeln("[{$this->config->getHost()}] Download file {$remote} to {$local}");
    }
}