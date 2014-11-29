<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Task\Scenario\Scenario;

class ScenarioTest extends \PHPUnit_Framework_TestCase
{
    public function testCreation()
    {
        $scenario = new Scenario('task');
        $this->assertEquals(['task'], $scenario->getTasks());
    }

    public function testBeforeAndAfter()
    {
        $scenario = new Scenario('task');
        $before = new Scenario('before');
        $after = new Scenario('after');

        $scenario->addBefore($before);
        $scenario->addAfter($after);

        $this->assertEquals(['before', 'task', 'after'], $scenario->getTasks());

        $scenario = new Scenario('0');

        $scenario->addBefore(new Scenario('-1'));
        $scenario->addBefore(new Scenario('-2'));
        $scenario->addAfter(new Scenario('1'));
        $scenario->addAfter(new Scenario('2'));

        $this->assertEquals(['-2', '-1', '0', '1', '2'], $scenario->getTasks());
    }
}
