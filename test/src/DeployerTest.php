<?php
/* (c) Anton Medvedev <anton@elfet.ru>
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
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
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
            ['scenarios'],
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
}
 