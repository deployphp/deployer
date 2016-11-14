<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

abstract class ServerBase implements ServerInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param $config
     */
    public function __construct(Configuration $config = null)
    {
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
    abstract public function connect();

    /**
     * {@inheritdoc}
     */
    abstract public function run($command);

    /**
     * {@inheritdoc}
     */
    abstract public function upload($local, $remote);

    /**
     * {@inheritdoc}
     */
    abstract public function download($local, $remote);

    /**
     * Parse commands before execution.
     *
     * @param string $command
     * @return string The parsed command
     */
    protected function parseCommand($command)
    {
        $env = [];

        $composerAuth = $this->getConfiguration()->getComposerAuth();
        if (!empty($composerAuth)) {
            $env = ['COMPOSER_AUTH' => sprintf('"%s"', str_replace('"', '\"', $composerAuth))] + $env;
        }

        $exports = '';
        foreach ($env as $key => $value) {
            $exports = sprintf('export %s=%s;', $key, $value);
        }

        return $exports . $command;
    }
}
