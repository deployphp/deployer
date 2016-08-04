<?php

/**
 * @author Irfan Durmus (http://github.com/irfan) <irfandurmus@gmail.com>
 * @package Deployer
 */

namespace Deployer\Cluster;

use Deployer\Console\Application;
use Deployer\Deployer;
use Deployer\Cluster\Node;

/**
 * @property Deployer $deployer
 */
class NodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Deployer $deployer
     */
    protected $deployer = null;

    /**
     * setup
     */
    public function setUp()
    {
        $app = new Application();
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->deployer = new Deployer($app, $input, $output);
    }

    /**
     * destroy
     */
    public function tearDown()
    {
        unset($this->deployer);
    }

    /**
     * test the initialize method
     */
    public function testInitialize()
    {
        $node = new Node();
        $node->setDeployer($this->deployer)
            ->setName('myClusterNode')
            ->setHost('domain.com')
            ->setPort(22)
            ->initialize();

        $this->assertInstanceOf('Deployer\Server\ServerInterface', $node->getServer());
        $this->assertInstanceOf('Deployer\Server\Builder', $node->getBuilder());
    }
}
