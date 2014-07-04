<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class ServerFactory
{
    /**
     * @var ServerInterface[]
     */
    private static $servers;

    /**
     * @param string $name
     * @param string $host
     * @param int $port
     * @return Configuration
     */
    public static function create($name, $host, $port = 22)
    {
        $configuration = new Configuration($host, $port);
        if (function_exists('ssh2_exec')) {
            self::$servers[$name] = new Ssh2($configuration);
        } else {
            self::$servers[$name] = new PhpSecLib($configuration);
        }
        return $configuration;
    }

    /**
     * @return ServerInterface[]
     */
    public static function getServers()
    {
        return self::$servers;
    }
}
