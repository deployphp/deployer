<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Exception\Exception;
use Symfony\Component\Yaml\Yaml;

class FileLoader
{
    /**
     * @var Host[]
     */
    private $hosts = [];

    /**
     * @param string $file
     * @return $this
     * @throws Exception
     */
    public function load($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new Exception("File `$file` doesn't exists or doesn't readable.");
        }

        $data = Yaml::parse(file_get_contents($file));

        if (!is_array($data)) {
            throw new Exception("Hosts file `$file` should contains array of hosts.");
        }

        foreach ($data as $hostname => $config) {
            if (preg_match('/^\./', $hostname)) {
                continue;
            }

            if (isset($config['local'])) {
                $host = new Localhost($hostname);
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
                    'sshOptions',
                    'sshFlags',
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

        return $this;
    }

    /**
     * @return Host[]
     */
    public function getHosts()
    {
        return $this->hosts;
    }
}
