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
        return $this->sftp->exec($command);
    }

    /**
     * {@inheritdoc}
     */
    public function upload($from, $to)
    {
        // TODO: Implement upload() method.
    }

    /**
     * {@inheritdoc}
     */
    public function download($to, $from)
    {
        // TODO: Implement download() method.
    }

} 