<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\Configuration;
use Deployer\Server\ServerInterface;
use Deployer\Server\SSHPipeInterface;
use Symfony\Component\Process\Process;

class NativeSsh implements ServerInterface, SSHPipeInterface
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
        $sshCommand = 'ssh ' . $this->buildCommandLineOptions($this->getSshConnectionOptions(), true) . ' ' . escapeshellarg($command);

        $process = new Process($sshCommand);
        $process
            ->setPty($this->getConfiguration()->getPty())
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

        $dir = dirname($remote);

        if (!in_array($dir, $this->mkdirs)) {
            $this->run('mkdir -p ' . escapeshellarg($dir));
            $this->mkdirs[] = $dir;
        }

        return $this->scpCopy($local, $serverConfig->getUserAndHost() . ':' . $remote);
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        $serverConfig = $this->getConfiguration();

        return $this->scpCopy($serverConfig->getUserAndHost() . ':' . $remote, $local);
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

        if ($serverConfig->getConfigFile()) {
            $scpOptions = array_merge_recursive($scpOptions, ['-F' => $serverConfig->getConfigFile()]);
        }

        if ($serverConfig->getPort()) {
            $scpOptions = array_merge_recursive($scpOptions, ['-P' => $serverConfig->getPort()]);
        }

        if ($serverConfig->getPrivateKey()) {
            $scpOptions = array_merge_recursive($scpOptions, ['-i' => $serverConfig->getPrivateKey()]);
        }

        if (\Deployer\get('ssh_multiplexing', false)) {
            $this->initMultiplexing();
            $scpOptions = array_merge_recursive($scpOptions, $this->getMultiplexingSshOptions());
        }

        $scpCommand = 'scp ' . $this->buildCommandLineOptions($scpOptions, true) . ' ' . escapeshellarg($target) . ' ' . escapeshellarg($target2);

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
     * Create if it isn't created before an ssh connection to a server and
     * pipe it to shell including standard I/O streams.
     *
     * @var string|null $initialCommand Command which will be run right after ssh connection.
     */
    public function createSshPipe($initialCommand = null)
    {
        $this->getConfiguration()->setPty(true);

        if (extension_loaded('pcntl')) {
            $this->createPcntlSshPipe($initialCommand);
        } else {
            $this->createProcOpenSshPipe($initialCommand);
        }
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
        $connectionData = $serverConfig->getUserAndHost() . ':' . $serverConfig->getPort();
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
            '-o' => [
                'ControlMaster=auto',
                'ControlPersist=5',
                'ControlPath=' . $this->getConnectionHash(),
            ],
        ];
    }

    /**
     * Returns final ssh connection command with configs, username and host.
     *
     * @return array
     */
    private function getSshConnectionOptions()
    {
        $sshOptions = [
            '-A' => null,
            '-q' => null,
            '-o' => ['UserKnownHostsFile=/dev/null', 'StrictHostKeyChecking=no'],
        ];

        if (\Deployer\get('ssh_multiplexing', false)) {
            $this->initMultiplexing();
            $sshOptions = array_merge_recursive($sshOptions, $this->getMultiplexingSshOptions());
        }

        $serverConfig = $this->getConfiguration();
        if ($serverConfig->getConfigFile()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-F' => $serverConfig->getConfigFile()]);
        }

        if (trim($serverConfig->getPort())) {
            $sshOptions = array_merge_recursive($sshOptions, ['-p' => $serverConfig->getPort()]);
        }

        if ($serverConfig->getPrivateKey()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-i' => $serverConfig->getPrivateKey()]);
        }

        if ($serverConfig->getPty()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-t' => null]);
        }

        $sshOptions = array_merge_recursive($sshOptions, [$serverConfig->getUserAndHost() => null]);

        return $sshOptions;
    }

    /**
     * Init multiplexing with exec() command
     *
     * Background: Symfony Process hangs on creating multiplex connection
     * but after mux is created with exec() then Symfony Process
     * can work with it.
     */
    private function initMultiplexing()
    {
        $serverConfig = $this->getConfiguration();

        $sshOptions = [];

        if ($serverConfig->getConfigFile()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-F' => $serverConfig->getConfigFile()]);
        }

        if ($serverConfig->getPort()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-p' => $serverConfig->getPort()]);
        }

        if ($serverConfig->getPrivateKey()) {
            $sshOptions = array_merge_recursive($sshOptions, ['-i' => $serverConfig->getPrivateKey()]);
        }

        $sshOptions = array_merge_recursive($sshOptions, $this->getMultiplexingSshOptions());

        $sshOptions = array_merge_recursive($sshOptions, [
            $serverConfig->getUserAndHost() => null,
        ]);

        exec('ssh -O check -S ' . $this->getConnectionHash() . ' ' . $this->buildCommandLineOptions($sshOptions, true) . ' 2>&1', $checkIfMuxActive);
        if (!preg_match('/Master running/', $checkIfMuxActive[0])) {
            if (\Deployer\isVerbose()) {
                \Deployer\writeln('  SSH multiplexing initialization');
            }
            exec('ssh ' . $this->buildCommandLineOptions($sshOptions, true) . "  'echo \"SSH multiplexing initialization\"' ");
        }
    }

    /**
     * @param string|null $initialCommand
     * @throws \RuntimeException if command is invalid
     */
    private function createPcntlSshPipe($initialCommand = null)
    {
        /**
         * [HACK] PCNTL works only on Unix and `pcntl_exec` requires full path of running command.
         * So it defines path of `ssh` using `which` utility.
         */
        $sshPath = trim(shell_exec('which ssh'));
        $sshArguments = explode(' ', $this->buildCommandLineOptions($this->getSshConnectionOptions(), false));

        if ($initialCommand !== null) {
            $sshArguments = array_merge($sshArguments, explode(' ', $initialCommand));
        }

        if (pcntl_exec($sshPath, $sshArguments) === false) {
            throw new \RuntimeException('Invalid command in `pcntl_exec`: ' . $sshPath . ' ' . implode(' ', $sshArguments));
        }
    }

    /**
     * @param string|null $initialCommand
     * @throws \RuntimeException if command is invalid
     */
    private function createProcOpenSshPipe($initialCommand = null)
    {
        $cmd = 'ssh ' . $this->buildCommandLineOptions($this->getSshConnectionOptions(), true);
        if ($initialCommand !== null) {
            $cmd .= ' ' . escapeshellarg($initialCommand);
        }

        $descriptors = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Invalid command in `proc_open`: ' . $cmd);
        }

        \Deployer\writeln(stream_get_contents($pipes[1]));

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);
    }

    /**
     * @param array $getSshConnectionOptions
     * @param bool $needToEscape
     *
     * @return string
     */
    private function buildCommandLineOptions($getSshConnectionOptions, $needToEscape)
    {
        $optionsParts = [];
        foreach ($getSshConnectionOptions as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $singleValue) {
                    $optionsParts[] = $key;
                    $optionsParts[] = $needToEscape ? escapeshellarg($singleValue) : $singleValue;
                }
            } else {
                $optionsParts[] = $key;
                if ($value !== null) {
                    $optionsParts[] = $needToEscape ? escapeshellarg($value) : $value;
                }
            }
        }

        return implode(' ', $optionsParts);
    }
}
