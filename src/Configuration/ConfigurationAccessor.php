<?php declare(strict_types=1);
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

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    /**
     * Get configuration options
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    /**
     * Check configuration option
     */
    public function has(string $name): bool
    {
        return $this->config->has($name);
    }

    /**
     * Set configuration option
     *
     * @param mixed $value
     * @return static
     */
    public function set(string $name, $value)
    {
        $this->config->set($name, $value);
        return $this;
    }

    /**
     * Add configuration option
     *
     * @param mixed[] $value
     * @return static
     */
    public function add(string $name, array $value)
    {
        $this->config->add($name, $value);
        return $this;
    }
}
