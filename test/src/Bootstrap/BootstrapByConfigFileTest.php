<?php

namespace Deployer\Bootstrap;

use Deployer\Deployer;
use Deployer\Console\Application;
use Deployer\Bootstrap\BootstrapByConfigFile;

/**
 * @property string $configFile
 * @property BootstrapByConfigFile $bootstrap
 */
class BootstrapByConfigFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string|null $configFile;
     */
    protected $configFile = null;
    
    /**
     * @var BootstrapByConfigFile | null $bootstrap
     */
    protected $bootstrap = null;

    /**
     * setUp the test
     */
    public function setUp()
    {
        $this->bootstrap = new BootstrapByConfigFile();
        $this->bootstrap->setConfig(__DIR__ . '/../../fixture/servers.yml');
    }

    /**
     * destroy after test has been completed
     */
    public function tearDown()
    {
        unset($this->configFile);
        unset($this->bootstrap);
    }
    
    /**
     * tests BootstrapByConfigfile::setConfig()
     */
    public function testSetConfig()
    {
        $this->assertEquals(__DIR__ . '/../../fixture/servers.yml', $this->bootstrap->configFile);
    }

    /**
     * tests is the bootstrap correct type
     */
    public function testIsBootstrapInstance()
    {
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $this->bootstrap);
    }

    /**
     * tests whether parseConfig returns correct value
     */
    public function testParseConfigReturns()
    {
        $bootstrap = $this->bootstrap->parseConfig();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);
    }

    /**
     * tests whether parseConfig sets properties correctly
     */
    public function testParseConfig()
    {
        $this->bootstrap->parseConfig();

        $this->assertArrayHasKey('production', $this->bootstrap->serverConfig);
        $this->assertArrayHasKey('beta', $this->bootstrap->serverConfig);
        $this->assertArrayHasKey('test', $this->bootstrap->serverConfig);

        $this->assertArrayHasKey('istanbul_dc_cluster', $this->bootstrap->clusterConfig);
    }

    /**
     * tests BootstrapByConfigFile::initServers()
     */
    public function testInitServers()
    {
        $bootstrap = $this->bootstrap->initServers();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);
        $this->assertContainsOnlyInstancesOf('Deployer\Server\Builder', $bootstrap->serverBuilders);
    }
    
    /**
     * tests BootstrapByConfigFile::initServers()
     */
    public function testInitClusters()
    {
        $bootstrap = $this->bootstrap->initClusters();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);

        $this->assertContainsOnlyInstancesOf('Deployer\Cluster\ClusterBuilder', $bootstrap->clusterBuilders);
    }
}
