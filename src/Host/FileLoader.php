<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Exception\ConfigurationException;
use Symfony\Component\Yaml\Yaml;

class FileLoader
{
    /**
     * @var Host[]
     */
    private $hosts = [];

    /**
     * @param string $file
     */
    public function load($file)
    {
        $data = Yaml::parse(file_get_contents($file));

        if (!is_array($data)) {
            throw new ConfigurationException("Hosts file `$file` should contains array of hosts.");
        }

        foreach ($data as $hostname => $config) {
            if (isset($config['local'])) {
                $host = new Localhost();
            } else {
                $host = new Host($hostname);
                $methods = [
                    'hostname',
                    'user',
                    'port',
                    'configFile',
                    'identityFile',
                    'forwardAgent',
                    'multiplexing',
                    'options',
                ];

                foreach ($methods as $method) {
                    if (isset($config[$method])) {
                        $host->$method($config[$method]);
                    }
                }
            }

            foreach ($config as $name => $value) {
                $host->set($name, $value);
            }

            $this->hosts[$hostname] = $host;
        }
    }

    /**
     * @return Host[]
     */
    public function getHosts()
    {
        return $this->hosts;
    }
}
