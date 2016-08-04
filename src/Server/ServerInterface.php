<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

interface ServerInterface
{
    /**
     * Connect to remote server.
     */
    public function connect();

    /**
     * Run shell command on remote server.
     * @param string $command
     * @param int    $timeout
     * @return string Output of command.
     */
    public function run($command, $timeout);

    /**
     * Upload file to remote server.
     * @param string $local Local path to file.
     * @param string $remote Remote path where upload.
     */
    public function upload($local, $remote);

    /**
     * Download file from remote server.
     * @param string $local Where to download file on local machine.
     * @param string $remote Which file to download from remote server.
     */
    public function download($local, $remote);
}
