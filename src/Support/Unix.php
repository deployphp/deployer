<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

class Unix
{
    /**
     * Expand leading tilde (~) symbol in given path.
     *
     * @param string $path
     * @return string
     */
    public static function parseHomeDir(string $path): string
    {
        if ('~' === $path || 0 === strpos($path, '~/')) {
            if (isset($_SERVER['HOME'])) {
                $home = $_SERVER['HOME'];
            } elseif (isset($_SERVER['HOMEDRIVE'], $_SERVER['HOMEPATH'])) {
                $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            } else {
                return $path;
            }

            return $home . substr($path, 1);
        }

        return $path;
    }
}
