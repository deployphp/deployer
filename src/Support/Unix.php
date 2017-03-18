<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

class Unix
{
    /**
     * Parse "~" symbol from path.
     *
     * @param string $path
     * @return string
     */
    public static function parseHomeDir(string $path): string
    {
        if (isset($_SERVER['HOME'])) {
            $path = str_replace('~', $_SERVER['HOME'], $path);
        } elseif (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
            $path = str_replace('~', $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'], $path);
        }

        return $path;
    }
}
