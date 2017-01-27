<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Cluster;

use Deployer\Console\Application;
use Deployer\Deployer;
use PHPUnit\Framework\TestCase;

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * Cluster
 */
class ClusterFactoryTest extends TestCase
{
    /**
     * @var Deployer $deployer
     */
    protected $deployer = null;

    /**
     * setup the test
     */
    public function setUp()
    {
        $app = new Application();
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');

        $this->deployer = new Deployer($app, $input, $output);
    }

    public function tearDown()
    {
        unset($this->deployer);
    }

    /**
     * test create function of the factory
     */

    public function testCreate()
    {
        $instance = ClusterFactory::create(
            $this->deployer,
            'myClusterNode',
            ['192.168.0.1', '192.168.0.2']
        );

        $this->assertInstanceOf('Deployer\Cluster\Cluster', $instance);
    }
}
