<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Remote;

use Deployer\Server\AbstractServer;
use Deployer\Server\Configuration;

class PhpSecLib extends AbstractServer
{
    /**
     * @var \Net_SFTP
     */
    private $sftp;

    /**
     * Array of created directories during upload.
     * @var array
     */
    private $directories = [];

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // Fix bug #434 in PhpSecLib
        set_include_path(__DIR__ . '/../../vendor/phpseclib/phpseclib/phpseclib/');

        $serverConfig = $this->getConfiguration();
        $this->sftp = new \Net_SFTP($serverConfig->getHost(), $serverConfig->getPort());

        switch ($serverConfig->getAuthenticationMethod()) {
            case Configuration::AUTH_BY_PASSWORD:

                $this->sftp->login($serverConfig->getUser(), $serverConfig->getPassword());

                break;

            case Configuration::AUTH_BY_PUBLIC_KEY:

                $key = new \Crypt_RSA();
                $key->setPassword($serverConfig->getPassPhrase());
                $key->loadKey(file_get_contents($serverConfig->getPrivateKey()));

                $this->sftp->login($serverConfig->getUser(), $key);

                break;

            case Configuration::AUTH_BY_PEM_FILE:

                $key = new \Crypt_RSA();
                $key->loadKey(file_get_contents($serverConfig->getPemFile()));
                $this->sftp->login($serverConfig->getUser(), $key);

                break;

            default:
                throw new \RuntimeException('You need to specify authentication method.');
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

        if ($this->sftp->getStdError()) {
            throw new \RuntimeException($this->sftp->getStdError());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        $this->checkConnection();

        $dir = dirname($remote);

        if (!isset($this->directories[$dir])) {
            $this->sftp->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }

        if (!$this->sftp->put($remote, $local, NET_SFTP_LOCAL_FILE)) {
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
}
