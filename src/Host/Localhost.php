<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

class Localhost
{
    use ConfigurationAccessor;

    private $hostname;

    /**
     * @param string $hostname
     */
    public function __construct(string $hostname = 'localhost')
    {
        $this->hostname = $hostname;
        $this->configuration = new Configuration();
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }
}
