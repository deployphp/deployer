<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class Environment
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param string $param
     * @param mixed $value
     */
    public function set($param, $value)
    {
        $this->parameters[$param] = $value;
    }

    /**
     * @param string $param
     * @param mixed $default
     * @return mixed
     * @throws \RuntimeException
     */
    public function get($param, $default = null)
    {
        if (array_key_exists($param, $this->parameters)) {
            return $this->parameters[$param];
        } else {
            if (null !== $default) {
                return $default;
            } else {
                throw new \RuntimeException("In current environment not exist parameter `$param`.");
            }
        }
    }

    /**
     * @param string $workingPath
     */
    public function setWorkingPath($workingPath)
    {
        $this->set('working_path', $workingPath);
    }

    /**
     * @return string
     */
    public function getWorkingPath()
    {
        return $this->get('working_path');
    }
}