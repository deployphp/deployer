<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Symfony\Component\Process\Process;

class Local implements ServerInterface
{
    const TIMEOUT = 300;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Local constructor.
     * @param $config
     */
    public function __construct(Configuration $config = null)
    {
        if ($config === null) {
            $config = new Configuration('localhost', 'localhost');
        }

        $this->configuration = $config;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        // We do not need to connect to local server.
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        return $this->mustRun($command);
    }

    /**
     * @param string $command
     * @param callable $callback
     * @return string
     */
    public function mustRun($command, $callback = null)
    {
        $process = new Process($command);
        $process
            ->setTimeout(self::TIMEOUT)
            ->setIdleTimeout(self::TIMEOUT)
            ->mustRun($callback);

        return $process->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function upload($local, $remote)
    {
        copy($local, $remote);
    }

    /**
     * {@inheritdoc}
     */
    public function download($local, $remote)
    {
        copy($remote, $local);
    }
}
