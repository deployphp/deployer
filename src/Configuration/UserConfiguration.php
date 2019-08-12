<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Configuration;

use Deployer\Support\Unix;

class UserConfiguration
{
    const HOSTNAME_COLORS = 'hostname_colors.json';

    public static function load($name, $default = null)
    {
        $configDir = Unix::parseHomeDir('~/.dep');
        if (!is_dir($configDir)) {
            mkdir($configDir);
        }
        $configPath = $configDir . '/' . $name;
        $config = $default;
        if (file_exists($configPath)) {
            $data = file_get_contents($configPath);
            $config = json_decode($data, true);
        }
        return $config;
    }

    public static function save($name, $config)
    {
        $configDir = Unix::parseHomeDir('~/.dep');
        if (!is_dir($configDir)) {
            mkdir($configDir);
        }
        $configPath = $configDir . '/' . $name;
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
    }
}
