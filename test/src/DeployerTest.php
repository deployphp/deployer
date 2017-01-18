<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\Application;

class DeployerTest extends \PHPUnit_Framework_TestCase
{
    private $deployer;

    protected function setUp()
    {
        $console = new Application();
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->deployer = new Deployer($console, $input, $output);
    }


    protected function tearDown()
    {
        unset($this->deployer);
    }

    public function collections()
    {
        return [
            ['tasks'],
            ['servers'],
            ['environments'],
        ];
    }

    public function testInstance()
    {
        $this->assertEquals($this->deployer, Deployer::get());
    }

    /**
     * @dataProvider collections
     */
    public function testCollections($collection)
    {
        $this->assertInstanceOf('Deployer\Collection\CollectionInterface', $this->deployer->{$collection});
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCollectionsE()
    {
        $this->deployer->some_collection;
    }

    /**
     * @expectedException
     * @expectedExceptionMessage "Key `no_name` is invalid"
     */
    public function testGetUndefinedDefault()
    {
        Deployer::getDefault('no_name');
    }

    public function testSetDefault()
    {
        Deployer::setDefault('a', 'b');
        $this->assertEquals('b', Deployer::getDefault('a'));
    }

    public function testAddDefault()
    {
        Deployer::setDefault('config', [
            'one',
            'two',
            'nested' => [],
        ]);
        Deployer::addDefault('config', [
            'three',
            'nested' => [
                'first',
            ],
        ]);
        Deployer::addDefault('config', [
            'nested' => [
                'second',
            ],
        ]);

        $expected = [
            'one',
            'two',
            'three',
            'nested' => [
                'first',
                'second',
            ],
        ];

        $this->assertEquals($expected, Deployer::getDefault('config'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Configuration parameter `config` isn't array.
     */
    public function testAddDefaultToNotArray()
    {
        Deployer::setDefault('config', 'option');
        Deployer::addDefault('config', ['three']);
    }
}
