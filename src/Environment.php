<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Server\Configuration;
use Deployer\Server\ServerInterface;

class Environment
{
    /**
     * @var Environment
     */
    private static $current;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var array
     */
    private $parameters = [];


    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    /**
     * @param Environment $current
     */
    public static function setCurrent($current)
    {
        self::$current = $current;
    }

    /**
     * @return Environment
     */
    public static function getCurrent()
    {
        return self::$current;
    }

    /**
     * @return ServerInterface
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->server->getConfiguration();
    }

    /**
     * @param string $param
     * @param mixed $value
     */
    public function set($param, $value)
    {
        $this->parameters[$param] = $value;
    }

    /**
     * @param string $param
     * @param mixed $default
     * @return mixed
     * @throws \RuntimeException
     */
    public function get($param, $default = null)
    {
        if (array_key_exists($param, $this->parameters)) {
            return $this->parameters[$param];
        } else {
            if (null !== $default) {
                return $default;
            } else {
                throw new \RuntimeException("In current environment not exist parameter `$param`.");
            }
        }
    }

    /**
     * @param string $releasePath
     */
    public function setReleasePath($releasePath)
    {
        $this->set('release_path', $releasePath);
    }

    /**
     * @return string
     */
    public function getReleasePath()
    {
        $releasePath = $this->get('release_path', false);

        if (false === $releasePath) {
            $releasePath = run("readlink -n current");
            $this->setReleasePath($releasePath);
        }

        return $releasePath;
    }

    /**
     * @return array
     */
    public function getReleases()
    {
        $releases = $this->server->run("cd {$this->getConfig()->getPath()} && ls releases");
        $releases = explode("\n", $releases);
        rsort($releases);

        return array_filter($releases, function ($release) {
            $release = trim($release);
            return !empty($release);
        });
    }
    
    /**
     * Let ls sort by time
     * No need to rsort
     */
    public function getReleasesByTime()
    {
        $releases = $this->server->run("cd {$this->getConfig()->getPath()} && ls -t releases");
        $releases = explode("\n", $releases);

        return array_filter($releases, function ($release) {
            $release = trim($release);
            return !empty($release);
        });
    }

    /**
     * @param string $workingPath
     */
    public function setWorkingPath($workingPath)
    {
        $this->set('working_path', $workingPath);
    }

    /**
     * @return string
     */
    public function getWorkingPath()
    {
        return $this->get('working_path');
    }
}
