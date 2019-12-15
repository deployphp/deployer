<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\CollectionInterface;
use Deployer\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployerTest extends TestCase
{
    private $deployer;

    protected function setUp(): void
    {
        $console = new Application();
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $this->deployer = new Deployer($console, $input, $output);
    }


    protected function tearDown(): void
    {
        unset($this->deployer);
    }

    public function testInstance()
    {
        $this->assertEquals($this->deployer, Deployer::get());
    }

    public function collections()
    {
        return [
            ['tasks'],
            ['hosts'],
        ];
    }

    /**
     * @dataProvider collections
     */
    public function testCollections($collection)
    {
        $this->assertInstanceOf(CollectionInterface::class, $this->deployer->{$collection});
    }

    public function testCollectionsE()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->deployer->some_collection;
    }

    public function testGetUndefinedDefault()
    {
        $this->assertNull(Deployer::getDefault('no_name'));
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
            'two' => 2,
            'nested' => [],
        ]);
        Deployer::addDefault('config', [
            'two' => 20,
            'nested' => [
                'first',
            ],
        ]);
        Deployer::addDefault('config', [
            'nested' => [
                'second',
            ],
        ]);
        Deployer::addDefault('config', [
            'extra',
        ]);

        $expected = [
            'one',
            'two' => 20,
            'nested' => [
                'first',
                'second',
            ],
            'extra',
        ];

        $this->assertEquals($expected, Deployer::getDefault('config'));
    }

    public function testAddDefaultToNotArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration parameter `config` isn\'t array.');

        Deployer::setDefault('config', 'option');
        Deployer::addDefault('config', ['three']);
    }
}
