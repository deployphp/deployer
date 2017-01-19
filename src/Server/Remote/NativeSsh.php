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
    const UNIX_SOCKET_MAX_LENGTH = 104;

    /**
     * @var array
     */
    private $mkdirs = [];

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
            '-o UserKnownHostsFile=/dev/null',
            '-o StrictHostKeyChecking=no'
        ];

        if (\Deployer\get('ssh_multiplexing', false)) {
            $this->initMultiplexing();
            $sshOptions = array_merge($sshOptions, $this->getMultiplexingSshOptions());
        }

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

        if ($serverConfig->getPty()) {
            $sshOptions[] = '-t';
        }

        $sshCommand = 'ssh ' . implode(' ', $sshOptions) . ' ' . escapeshellarg($username . $hostname) . ' ' . escapeshellarg($command);

        $process = new Process($sshCommand);
        $process
            ->setPty($serverConfig->getPty())
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

        $dir = dirname($remote);

        if (!in_array($dir, $this->mkdirs)) {
            $this->run('mkdir -p ' . escapeshellarg($dir));
            $this->mkdirs[] = $dir;
        }

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
            $scpOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }

        if (\Deployer\get('ssh_multiplexing', false)) {
            $this->initMultiplexing();
            $scpOptions = array_merge($scpOptions, $this->getMultiplexingSshOptions());
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

    /**
     * Return ssh multiplexing socket name
     *
     * When $connectionHash is longer than 104 chars we can get "SSH Error: unix_listener: too long for Unix domain socket".
     * https://github.com/ansible/ansible/issues/11536
     * So try to get as descriptive hash as possible.
     * %C is creating hash out of connection attributes.
     *
     * @return string Ssh multiplexing socket name
     */
    protected function getConnectionHash()
    {
        $serverConfig = $this->getConfiguration();
        $connectionData = "{$serverConfig->getUser()}@{$serverConfig->getHost()}:{$serverConfig->getPort()}";
        $tryLongestPossibleSocketName = 0;

        $connectionHash = '';
        do {
            switch ($tryLongestPossibleSocketName) {
                case 1:
                    $connectionHash = "~/.ssh/deployer_mux_" . $connectionData;
                    break;
                case 2:
                    $connectionHash = "~/.ssh/deployer_mux_%C";
                    break;
                case 3:
                    $connectionHash = "~/deployer_mux_$connectionData";
                    break;
                case 4:
                    $connectionHash = "~/deployer_mux_%C";
                    break;
                case 5:
                    $connectionHash = "~/mux_%C";
                    break;
                case 6:
                    throw new \RuntimeException("The multiplexing socket name is too long. Socket name is:" . $connectionHash);
                default:
                    $connectionHash = "~/.ssh/deployer_mux_$connectionData";
            }
            $tryLongestPossibleSocketName++;
        } while (strlen($connectionHash) > self::UNIX_SOCKET_MAX_LENGTH);

        return $connectionHash;
    }


    /**
     * Return ssh options for multiplexing
     *
     * @return string[]
     */
    protected function getMultiplexingSshOptions()
    {
        return [
            '-o ControlMaster=auto',
            '-o ControlPersist=5',
            '-o ControlPath=\'' . $this->getConnectionHash() . '\'',
        ];
    }


    /**
     * Init multiplexing with exec() command
     *
     * Background: Symfony Process hangs on creating multiplex connection
     * but after mux is created with exec() then Symfony Process
     * can work with it.
     */
    public function initMultiplexing()
    {
        $serverConfig = $this->getConfiguration();
        $username = $serverConfig->getUser() ? $serverConfig->getUser() . '@' : null;
        $hostname = $serverConfig->getHost();

        $sshOptions = [];
        if ($serverConfig->getPort()) {
            $sshOptions[] = '-p ' . escapeshellarg($serverConfig->getPort());
        }
        if ($serverConfig->getPrivateKey()) {
            $sshOptions[] = '-i ' . escapeshellarg($serverConfig->getPrivateKey());
        }
        $sshOptions = array_merge($sshOptions, $this->getMultiplexingSshOptions());

        exec('ssh ' . implode(' ', $sshOptions) . ' -O check -S ' . $this->getConnectionHash() . ' ' . escapeshellarg($username . $hostname) . ' 2>&1', $checkifMuxActive);
        if (!preg_match('/Master running/', $checkifMuxActive[0])) {
            if (\Deployer\isVerbose()) {
                \Deployer\writeln('  SSH multiplexing initialization');
            }
            exec('ssh ' . implode(' ', $sshOptions) . ' ' . escapeshellarg($username . $hostname) . "  'echo \"SSH multiplexing initialization\"' ");
        }
    }
}
