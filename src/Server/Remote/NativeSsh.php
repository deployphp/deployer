<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\Configuration;
use Deployer\Server\ServerInterface;
use Symfony\Component\Process\Process;

class NativeSsh implements ServerInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Connect to remote server.
     */
    public function connect()
    {
        /* no persistent connection is used */
    }

    /**
     * Run shell command on remote server.
     * @param string $command
     * @return string Output of command.
     */
    public function run($command)
    {
        $serverConfig = $this->getConfiguration();
        $sshOptions = ['-A'];

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        if (!empty($username)) {
            $username = $username . '@';
        }
        $hostname = $serverConfig->getHost();

        if ($serverConfig->getPort()) {
            $sshOptions[] = '-p ' . escapeshellarg($serverConfig->getPort());
        }

        if ($serverConfig->getPrivateKey()) {
            $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }

        $sshCommand = 'ssh ' . implode(' ', $sshOptions) . ' ' . escapeshellarg($username . $hostname) . ' ' . escapeshellarg($command);

        $process = new Process($sshCommand);
        $process
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->mustRun();

        return $process->getOutput();
    }

    /**
     * Upload file to remote server.
     * @param string $local Local path to file.
     * @param string $remote Remote path where upload.
     */
    public function upload($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        return $this->scpCopy($local, (!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote);
    }

    /**
     * Download file from remote server.
     * @param string $local Where to download file on local machine.
     * @param string $remote Which file to download from remote server.
     */
    public function download($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        return $this->scpCopy((!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote, $local);
    }

    /**
     * Copy file from target1 to target 2 via scp
     * @param string $target
     * @param string $target2
     */
    public function scpCopy($target, $target2)
    {
        $serverConfig = $this->getConfiguration();

        $scpOptions = [];

        if ($serverConfig->getPort()) {
            $scpOptions[] = '-P ' . escapeshellarg($serverConfig->getPort());
        }

        if ($serverConfig->getPrivateKey()) {
            $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }

        $scpCommand = 'scp ' . implode(' ', $scpOptions) . ' ' . escapeshellarg($target) . ' ' . escapeshellarg($target2);

        $process = new Process($scpCommand);
        $process
            ->setTimeout(null)
            ->setIdleTimeout(null)
            ->mustRun();

        return $process->getOutput();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
