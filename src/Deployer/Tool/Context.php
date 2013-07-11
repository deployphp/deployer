<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Tool;

use Deployer\Tool;

class Context
{
    /**
     * @var Tool[]
     */
    public static $context = array();

    /**
     * @param Tool $context
     */
    public static function push(Tool $context)
    {
        self::$context[] = $context;
    }

    /**
     * @return Tool
     * @throws \RuntimeException
     */
    public static function get()
    {
        if(empty(self::$context)) {
            throw new \RuntimeException('Context is empty');
        }

        return end(self::$context);
    }

    /**
     * @return Tool
     * @throws \RuntimeException
     */
    public static function pop()
    {
        if(empty(self::$context)) {
            throw new \RuntimeException('Context is empty');
        }

        return array_pop(self::$context);
    }

    /**
     * Clear all context.
     */
    public static function clear()
    {
        self::$context = array();
    }
}