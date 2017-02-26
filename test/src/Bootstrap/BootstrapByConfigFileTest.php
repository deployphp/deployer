<?php

namespace Deployer;

use Deployer\Bootstrap\BootstrapByConfigFile;
use Deployer\Collection\Collection;
use Deployer\Server\Builder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @property string $configFile
 * @property BootstrapByConfigFile $bootstrap
 */
class BootstrapByConfigFileTest extends TestCase
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
        $this->assertArrayHasKey('localhost', $this->bootstrap->serverConfig);
        $this->assertArrayHasKey('istanbul_dc_cluster', $this->bootstrap->clusterConfig);
    }

    /**
     * tests whether parseConfig throws exception if  correctly
     * @expectedException \RuntimeException
     */
    public function testParseConfigThrowsExceptionIfConfigFileFails()
    {
        $this->bootstrap->setConfig(__DIR__ . '/../../fixture/servers-empty.yml');
        $this->bootstrap->parseConfig();
    }

    /**
     * tests BootstrapByConfigFile::initServers()
     */
    public function testInitServers()
    {
        $bootstrap = $this->bootstrap->initServers();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);
        $this->assertContainsOnlyInstancesOf('Deployer\Builder\BuilderInterface', $bootstrap->serverBuilders);
    }

    /**
     * tests BootstrapByConfigFile::initServers()
     * @expectedException \RuntimeException
     */
    public function testInitServersThrowsExceptionIfConfigFileIsNotFullConfigured()
    {
        $this->bootstrap->serverConfig = ['production' => null];
        $bootstrap = $this->bootstrap->initServers();

        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);
    }

    /**
     * tests BootstrapByConfigFile::initClusters()
     */
    public function testInitClusters()
    {
        $bootstrap = $this->bootstrap->initClusters();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);

        $this->assertContainsOnlyInstancesOf('Deployer\Builder\BuilderInterface', $bootstrap->clusterBuilders);
    }

    /**
     * tests BootstrapByConfigFile::initClusters()
     * @expectedException \RuntimeException
     */
    public function testInitClustersThrowsExceptionIfConfigFileIsNotFullConfigured()
    {
        $this->bootstrap->clusterConfig = ['production' => null];
        $bootstrap = $this->bootstrap->initClusters();
        $this->assertInstanceOf('Deployer\Bootstrap\BootstrapByConfigFile', $bootstrap);
    }

    public function testExecuteBuilderMethodsWithAllConfigs()
    {
        $class = new ReflectionClass(get_class($this->bootstrap));
        $targetMethod = $class->getMethod('executeBuilderMethods');
        $targetMethod->setAccessible(true);

        $configs = new Collection([
            'identity_file' => [
                'public_key' => 'public_key',
                'private_key' => 'private_key',
                'password' => 'password',
            ],
            'identity_config' => 'identity_config',
            'forward_agent' => 'forward_agent',
            'user' => 'user',
            'password' => 'password',
            'stage' => 'stage',
            'pem_file' => 'pem_file',
        ]);

        $builderStub = $this->getMockBuilder(Builder::class)
            ->setMethods(['identityFile', 'configFile', 'forwardAgent', 'user', 'password', 'stage', 'pemFile', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $builderStub->expects($this->once())->method('identityFile');
        $builderStub->expects($this->once())->method('configFile');
        $builderStub->expects($this->once())->method('forwardAgent');
        $builderStub->expects($this->once())->method('user');
        $builderStub->expects($this->once())->method('password');
        $builderStub->expects($this->once())->method('stage');
        $builderStub->expects($this->once())->method('pemFile');
        $builderStub->expects($this->never())->method('set');

        $targetMethod->invoke($this->bootstrap, $configs, $builderStub);
    }

    public function testExecuteBuilderMethodsWithDefaultConfigs()
    {
        $class = new ReflectionClass(get_class($this->bootstrap));
        $targetMethod = $class->getMethod('executeBuilderMethods');
        $targetMethod->setAccessible(true);

        $configs = new Collection([
            'identity_file' => null,
            'identity_config' => null,
            'custom' => 'custom',
        ]);

        $builderStub = $this->getMockBuilder(Builder::class)
            ->setMethods(['identityFile', 'configFile', 'forwardAgent', 'user', 'password', 'stage', 'pemFile', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $builderStub->expects($this->once())->method('identityFile');
        $builderStub->expects($this->once())->method('configFile');
        $builderStub->expects($this->never())->method('forwardAgent');
        $builderStub->expects($this->never())->method('user');
        $builderStub->expects($this->never())->method('password');
        $builderStub->expects($this->never())->method('stage');
        $builderStub->expects($this->never())->method('pemFile');
        $builderStub->expects($this->once())->method('set');

        $targetMethod->invoke($this->bootstrap, $configs, $builderStub);
    }
}
