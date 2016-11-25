<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Util;

class Composer
{
    public static function getComposerAuth()
    {
        $composerAuth = '';

        $environment = getenv('COMPOSER_AUTH');
        if ($environment) {
            $composerAuth = json_decode($environment);
        }

        if (empty($composerAuth)) {
            $filename = self::getComposerHomeDir() . DIRECTORY_SEPARATOR . 'auth.json';
            if (file_exists(realpath($filename))) {
                $composerAuth = json_decode(file_get_contents($filename));
            }
        }

        if (!empty($composerAuth)) {
            return json_encode($composerAuth);
        }
        return '';
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    public static function getComposerHomeDir()
    {
        $home = getenv('COMPOSER_HOME');
        if ($home) {
            return $home;
        }

        if (Platform::isWindows()) {
            if (!getenv('APPDATA')) {
                throw new \RuntimeException('The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly');
            }

            return rtrim(strtr(getenv('APPDATA'), '\\', '/'), '/') . '/Composer';
        }

        $userDir = self::getUserDir();
        if (is_dir($userDir . '/.composer')) {
            return $userDir . '/.composer';
        }

        if (self::useXdg()) {
            // XDG Base Directory Specifications
            $xdgConfig = getenv('XDG_CONFIG_HOME') ?: $userDir . '/.config';

            return $xdgConfig . '/composer';
        }

        return $userDir . '/.composer';
    }

    /**
     * @return bool
     */
    private static function useXdg()
    {
        foreach (array_keys($_SERVER) as $key) {
            if (substr($key, 0, 4) === 'XDG_') {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    private static function getUserDir()
    {
        $home = getenv('HOME');
        if (!$home) {
            throw new \RuntimeException('The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly');
        }

        return rtrim(strtr($home, '\\', '/'), '/');
    }
}
