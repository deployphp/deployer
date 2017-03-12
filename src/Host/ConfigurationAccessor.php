<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

trait ConfigurationAccessor
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
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
        return $this->configuration->get($name, $default);
    }

    /**
     * Check configuration option
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name)
    {
        return $this->configuration->has($name);
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
        $this->configuration->set($name, $value);
        return $this;
    }

    /**
     * Add Configuration option
     *
     * @param string $name
     * @param array|bool|int|string $value
     * @return $this
     */
    public function add(string $name, $value)
    {
        $this->configuration->add($name, $value);
        return $this;
    }
}
