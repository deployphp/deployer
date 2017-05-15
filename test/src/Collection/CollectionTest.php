<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Collection;

use Deployer\Host\HostCollection;
use Deployer\Task\TaskCollection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public static function collections()
    {
        return [
            [new Collection()],
            [new TaskCollection()],
            [new HostCollection()],
        ];
    }

    /**
     * @dataProvider collections
     */
    public function testCollection($collection)
    {
        $this->assertInstanceOf(CollectionInterface::class, $collection);

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

        $this->assertEquals($collection->select(function ($value, $key) use ($object) {
            return $value === $object && $key === 'object';
        }), ['object' => $object]);
    }

    /**
     * @dataProvider collections
     * @depends      testCollection
     */
    public function testException($collection)
    {
        $this->expectException(\InvalidArgumentException::class);
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
