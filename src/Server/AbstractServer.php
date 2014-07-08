<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

abstract class AbstractServer implements ServerInterface
{
    /**
     * Server config.
     * @var Configuration
     */
    protected $config;

    /**
     * Server env.
     * @var Environment
     */
    protected $environment;

    /**
     * @param Configuration $environment
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->environment = new Environment($this);
    }

    /**
     *{@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
}