<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Collection\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
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
        $this->assertInstanceOf(Collection::class, $this->deployer->{$collection});
    }

    public function testCollectionsE()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->deployer->some_collection;
    }
}
