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

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return 'localhost';
    }
}
