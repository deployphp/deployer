<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task\Scenario;

use Deployer\Collection\Collection;
use Deployer\Task\Scenario\Scenario;

class ScenarioCollection extends Collection
{
    /**
     * @param string $name
     * @return Scenario
     */
    public function get($name)
    {
        return parent::get($name);
    }
}
