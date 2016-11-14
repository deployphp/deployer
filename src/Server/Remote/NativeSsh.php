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
     * {@inheritdoc}
     */
    public function connect()
    {
        /* No persistent connection is used */
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $serverConfig = $this->getConfiguration();
        $sshOptions = [
            '-A',
            '-q',
            '-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no'
        ];

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
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        $username = $serverConfig->getUser() ? $serverConfig->getUser() : null;
        $hostname = $serverConfig->getHost();

        return $this->scpCopy($local, (!empty($username) ? $username . '@' : '') . $hostname . ':' . $remote);
    }

    /**
     * {@inheritdoc}
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
     * @return string
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
