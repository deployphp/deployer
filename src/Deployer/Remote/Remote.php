<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Remote;

use Deployer\Tool\Remote\Key;
use Symfony\Component\Finder\SplFileInfo;

class Remote implements RemoteInterface
{
    private $server;

    private $user;

    private $sftp;

    private $cd = null;

    private $directories = array();

    public function __construct($server, $user, $password)
    {
        $this->server = $server;
        $this->user = $user;
        $this->sftp = new \Net_SFTP($server);

        if ($password instanceof Key) {
            $password = $password->key();
        }

        if (!$this->sftp->login($user, $password)) {
            throw new \RuntimeException("Can not login to server \"$server\".");
        }
    }

    public function cd($directory)
    {
        $this->cd = $directory;
    }

    public function execute($command)
    {
        if (null !== $this->cd) {
            $path = escapeshellarg($this->cd);
            $command = "cd $path && $command";
        }
        return $this->sftp->exec($command);
    }

    public function uploadFile($from, $to)
    {
        $dir = dirname($to);
        if (!isset($this->directories[$dir])) {
            $this->sftp->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }
        $this->sftp->put($to, $from, NET_SFTP_LOCAL_FILE);
    }
}
