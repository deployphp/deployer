<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Ssh;

class Ssh2 implements ServerInterface
{
    /**
     * SSH session.
     * @var Ssh\Session
     */
    private $session;

    /**
     * Server config.
     * @var Configuration
     */
    private $config;

    /**
     * Array of created directories during upload.
     * @var array
     */
    private $directories = [];

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $configuration = new Ssh\Configuration($this->config->getHost(), $this->config->getPort());

        switch ($this->config->getAuthenticationMethod()) {
            case Configuration::AUTH_BY_PASSWORD:
                $authentication = new Ssh\Authentication\Password(
                    $this->config->getUser(),
                    $this->config->getPassword()
                );
                break;

            case Configuration::AUTH_BY_CONFIG:
                $configuration = new Ssh\SshConfigFileConfiguration(
                    $this->config->getConfigFile(),
                    $this->config->getHost(),
                    $this->config->getPort()
                );
                $authentication = $configuration->getAuthentication(
                    $this->config->getPassword(),
                    $this->config->getUser()
                );

                break;

            case Configuration::AUTH_BY_PUBLIC_KEY:

                $authentication = new Ssh\Authentication\PublicKeyFile(
                    $this->config->getUser(),
                    $this->config->getPublicKey(),
                    $this->config->getPrivateKey(),
                    $this->config->getPassPhrase()
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

        return $this->session->getExec()->run($command);
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        $this->checkConnection();

        $dir = dirname($remote);

        if (!isset($this->directories[$dir])) {
            $this->session->getSftp()->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }

        if (!$this->session->getSftp()->send($local, $remote)) {
            throw new \RuntimeException('Can not upload file.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        $this->checkConnection();

        if(!$this->session->getSftp()->receive($remote, $local)) {
            throw new \RuntimeException('Can not download file.');
        }
    }

    /**
     *{@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->config;
    }
} 