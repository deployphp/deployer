<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Configuration;

trait ConfigurationAccessor
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get configuration options
     *
     * @param string $name
     * @param null $default
     * @return array|bool|int|string
     */
    public function get(string $name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    /**
     * Check configuration option
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name)
    {
        return $this->config->has($name);
    }

    /**
     * Set configuration option
     *
     * @param string $name
     * @param array|bool|int|string $value
     * @return $this
     */
    public function set(string $name, $value)
    {
        $this->config->set($name, $value);
        return $this;
    }

    /**
     * Add configuration option
     *
     * @param string $name
     * @param array $value
     * @return $this
     */
    public function add(string $name, array $value)
    {
        $this->config->add($name, $value);
        return $this;
    }
}
