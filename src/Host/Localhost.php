<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

class Localhost extends Host
{
    /**
     * @param string $hostname
     */
    public function __construct(string $hostname = 'localhost')
    {
        parent::__construct($hostname);
    }
}
