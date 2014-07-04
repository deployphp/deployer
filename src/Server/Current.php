<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class Current 
{
    /**
     * @var string
     */
    private static $name;

    /**
     * @var ServerInterface
     */
    private static $server;

    /**
     * @param ServerInterface $server
     */
    public static function setServer($name, $server)
    {
        self::$name = $name;
        self::$server = $server;
    }

    /**
     * @return ServerInterface
     */
    public static function getServer()
    {
        return self::$server;
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return self::$name;
    }
} 