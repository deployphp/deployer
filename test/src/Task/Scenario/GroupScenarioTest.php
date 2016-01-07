<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task\Scenario;

class GroupScenarioTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupScenario()
    {
        $s1 = new Scenario('s1');
        $s2 = new Scenario('s2');
        $group = new GroupScenario([$s1, $s2]);

        $this->assertEquals(['s1', 's2'], $group->getTasks());
    }
}
