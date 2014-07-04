<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class PhpSecLib implements ServerInterface
{
    /**
     * @var Configuration
     */
    private $config;

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
        $this->sftp = new \Net_SFTP($this->config->getHost(), $this->config->getPort());

        switch ($this->config->getAuthenticationMethod()) {
            case Configuration::AUTH_BY_PASSWORD:

                $this->sftp->login($this->config->getUser(), $this->config->getPassword());

                break;

            case Configuration::AUTH_BY_PUBLIC_KEY:

                $key = new \Crypt_RSA();
                $key->setPassword($this->config->getPassPhrase());
                $key->loadKey(file_get_contents($this->config->getPrivateKey()));

                $this->sftp->login($this->config->getUser(), $key);

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