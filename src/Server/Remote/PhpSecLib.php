<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\Configuration;
use Deployer\Server\ServerInterface;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\System\SSH\Agent;
use RuntimeException;

class PhpSecLib implements ServerInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SFTP
     */
    private $sftp;

    /**
     * Array of created directories during upload.
     * @var array
     */
    private $directories = [];

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
        $serverConfig = $this->getConfiguration();
        $this->sftp = new SFTP($serverConfig->getHost(), $serverConfig->getPort(), 3600);

        switch ($serverConfig->getAuthenticationMethod()) {
            case Configuration::AUTH_BY_PASSWORD:

                $result = $this->sftp->login($serverConfig->getUser(), $serverConfig->getPassword());

                break;

            case Configuration::AUTH_BY_IDENTITY_FILE:

                $key = new RSA();
                $key->setPassword($serverConfig->getPassPhrase());
                $key->loadKey(file_get_contents($serverConfig->getPrivateKey()));

                $result = $this->sftp->login($serverConfig->getUser(), $key);

                break;

            case Configuration::AUTH_BY_PEM_FILE:

                $key = new RSA();
                $key->loadKey(file_get_contents($serverConfig->getPemFile()));
                $result = $this->sftp->login($serverConfig->getUser(), $key);

                break;

            case Configuration::AUTH_BY_AGENT:

                $key = new Agent();
                $key->startSSHForwarding(null);
                $result = $this->sftp->login($serverConfig->getUser(), $key);

                break;

            case Configuration::AUTH_BY_IDENTITY_FILE_AND_PASSWORD:

                $key = new RSA();
                $key->setPassword($serverConfig->getPassPhrase());
                $key->loadKey(file_get_contents($serverConfig->getPrivateKey()));

                $result = $this->sftp->login($serverConfig->getUser(), $key, $serverConfig->getPassword());

                break;

            default:
                throw new RuntimeException('You need to specify authentication method.');
        }

        if (!$result) {
            throw new RuntimeException('Unable to login with the provided credentials.');
        }
    }

    /**
     * Check if not connected and connect.
     */
    public function checkConnection()
    {
        if (null === $this->sftp) {
            $this->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $this->checkConnection();

        $result = $this->sftp->exec($command);

        if ($this->sftp->getExitStatus() !== 0) {
            $output = $this->sftp->getStdError() ?: $result;
            throw new \RuntimeException($output);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        $this->checkConnection();

        $remote = str_replace(DIRECTORY_SEPARATOR, '/', $remote);
        $dir = str_replace(DIRECTORY_SEPARATOR, '/', dirname($remote));

        if (!isset($this->directories[$dir])) {
            $this->sftp->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }

        if (!$this->sftp->put($remote, $local, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException(implode($this->sftp->getSFTPErrors(), "\n"));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        $this->checkConnection();

        if (!$this->sftp->get($remote, $local)) {
            throw new \RuntimeException(implode($this->sftp->getSFTPErrors(), "\n"));
        }
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
