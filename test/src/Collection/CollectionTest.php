<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

use Deployer\Server\EnvironmentCollection;
use Deployer\Server\GroupCollection;
use Deployer\Server\ServerCollection;
use Deployer\Task\Scenario\ScenarioCollection;
use Deployer\Task\TaskCollection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public static function collections()
    {
        return [
            [new Collection()],
            [new TaskCollection()],
            [new ScenarioCollection()],
            [new ServerCollection()],
            [new EnvironmentCollection()],
        ];
    }

    /**
     * @dataProvider collections
     */
    public function testCollection($collection)
    {
        $this->assertInstanceOf('Deployer\Collection\CollectionInterface', $collection);

        $object = new \stdClass();
        $collection->set('object', $object);

        $this->assertTrue($collection->has('object'));
        $this->assertEquals($object, $collection->get('object'));

        $this->assertInstanceOf('Traversable', $collection);

        $traversable = false;
        foreach ($collection as $i) {
            $traversable = $i === $object;
        }

        $this->assertTrue($traversable, 'Collection does not traversable.');
    }

    /**
     * @dataProvider collections
     * @depends      testCollection
     * @expectedException \RuntimeException
     */
    public function testException($collection)
    {
        $collection->get('unexpected');
    }

    public function testArrayAccess()
    {
        $collection = new Collection();

        $collection['key'] = 'value';
        $this->assertEquals('value', $collection['key']);

        $this->assertTrue(isset($collection['key']));

        unset($collection['key']);
        $this->assertFalse(isset($collection['key']));
    }
}
