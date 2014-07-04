<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

interface ServerInterface 
{
    /**
     * Run shell command on remote server.
     * @param string $command
     */
    public function run($command);

    /**
     * Upload file to remote server.
     * @param string $from Local path to file.
     * @param string $to Remote path where upload.
     */
    public function upload($from, $to);

    /**
     * Download file from remote server.
     * @param string $to Where to download file on local machine.
     * @param string $from Which file to download from remote server.
     */
    public function download($to, $from);
}