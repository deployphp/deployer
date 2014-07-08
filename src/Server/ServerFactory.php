<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\Deployer;

class ServerFactory
{
    /**
     * @param string $name
     * @param string $host
     * @param int $port
     * @return Configuration
     */
    public static function create($name, $host, $port = 22)
    {
        $configuration = new Configuration($name, $host, $port);
        if (get('use_ssh2', function_exists('ssh2_exec'))) {
            Deployer::$servers[$name] = new Ssh2($configuration);
        } else {
            Deployer::$servers[$name] = new PhpSecLib($configuration);
        }
        return $configuration;
    }
}
