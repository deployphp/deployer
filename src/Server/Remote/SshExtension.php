<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\ServerInterface;
use Deployer\Server\Configuration;
use Ssh;

class SshExtension implements ServerInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SSH session.
     * @var Ssh\Session
     */
    private $session;

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
        $configuration = new Ssh\Configuration($serverConfig->getHost(), $serverConfig->getPort());

        switch ($serverConfig->getAuthenticationMethod()) {
            case Configuration::AUTH_BY_PASSWORD:
                $authentication = new Ssh\Authentication\Password(
                    $serverConfig->getUser(),
                    $serverConfig->getPassword()
                );
                break;

            case Configuration::AUTH_BY_CONFIG:
                $configuration = new Ssh\SshConfigFileConfiguration(
                    $serverConfig->getConfigFile(),
                    $serverConfig->getHost(),
                    $serverConfig->getPort()
                );
                $authentication = $configuration->getAuthentication(
                    $serverConfig->getPassword(),
                    $serverConfig->getUser()
                );

                break;

            case Configuration::AUTH_BY_IDENTITY_FILE:

                $authentication = new Ssh\Authentication\PublicKeyFile(
                    $serverConfig->getUser(),
                    $serverConfig->getPublicKey(),
                    $serverConfig->getPrivateKey(),
                    $serverConfig->getPassPhrase()
                );

                break;

            case Configuration::AUTH_BY_PEM_FILE:

                throw new \RuntimeException('If you want to use pem file, switch to using PhpSecLib.');

            case Configuration::AUTH_BY_AGENT:

                $authentication = new \Ssh\Authentication\Agent(
                    $serverConfig->getUser()
                );
                break;

            default:
                throw new \RuntimeException('You need to specify authentication method.');
        }

        $this->session = new Ssh\Session($configuration, $authentication);
    }

    /**
     * Check if not connected and connect.
     */
    public function checkConnection()
    {
        if (null === $this->session) {
            $this->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $this->checkConnection();

        $pty = $this->getConfiguration()->getPty();
        return $this->session->getExec()->run($command, $pty);
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
            $this->session->getSftp()->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }

        if ($this->session->getSftp()->send($local, $remote) === false) {
            throw new \RuntimeException('Can not upload file.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        $this->checkConnection();

        if (!$this->session->getSftp()->receive($remote, $local)) {
            throw new \RuntimeException('Can not download file.');
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
