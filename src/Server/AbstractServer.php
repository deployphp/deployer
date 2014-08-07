<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Environment;

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
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->environment = new Environment();
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

    /**
     * @return array
     */
    public function getReleases()
    {
        $releases = $this->run("cd {$this->config->getPath()} && ls releases");
        $releases = explode("\n", $releases);
        rsort($releases);

        return array_filter($releases, function ($release) {
            $release = trim($release);
            return !empty($release);
        });
    }


    /**
     * @param string $releasePath
     */
    public function setReleasePath($releasePath)
    {
        $this->environment->set('release_path', $releasePath);
    }

    /**
     * @return string
     */
    public function getReleasePath()
    {
        $releasePath = $this->environment->get('release_path');

        if (null === $releasePath) {
            $releasePath = $this->run("readlink -n current");
            $this->setReleasePath($releasePath);
        }

        return $releasePath;
    }
}