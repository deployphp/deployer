<?php

namespace Deployer\Host;

use Deployer\Exception\Exception;
use PHPUnit\Framework\TestCase;

class HostSelectorTest extends TestCase
{
    public function testCanBeCreatedFromEmptyHostCollection()
    {
        $hostSelector = new HostSelector(new HostCollection());
        $classname = 'Deployer\Host\HostSelector';

        $this->assertInstanceOf($classname, $hostSelector);
    }

    /**
     * @expectedException Exception
     */
    public function testThrowExceptionIfStageOrHostnameNotFound()
    {
        $hostSelector = new HostSelector(new HostCollection());
        $hostSelector->getHosts('ThisHostDoNotExists');
    }

    /**
     * @dataProvider dataProviderForHostnames
     */
    public function testReturnArrayWithHostnameThatWasSet($hostname, $host)
    {
        $hostCollection = new HostCollection();
        $hostCollection->set($hostname, $host);
        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getHosts(null);

        $this->assertSame($hostname, key($hosts));
    }

    public function dataProviderForHostnames()
    {
        return [
            ['test', new Host('test')],
            ['app-server', new Host('app-server')],
            ['db', new Host('db')],
            ['varnish-cache', new Host('varnish-cache')],
            ['staging', new Host('staging')],
        ];
    }

    public function testReturnArrayWithDefaultLocalHostForEmptyCollection()
    {
        $hostSelector = new HostSelector(new HostCollection());
        $hosts = $hostSelector->getHosts(null);

        $this->assertSame('localhost', key($hosts));
    }

    public function testReturnCorrectSizeOfHostsArray()
    {
        $hostCollection = new HostCollection();

        for($index = 0; $index < 100; $index++) {
            $hostCollection->set("host$index", new Host("host$index"));
        }

        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getHosts(null);

        $this->assertSame(count($hosts), 100);
    }

    public function testGetByHostnameReturnsArrayWithHostsAndCorrectLength()
    {
        $hostCollection = new HostCollection();
        $hostCollection->set('server', new Host('server'));
        $hostCollection->set('app', new Host('app'));
        $hostCollection->set('db', new Host('db'));
        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getByHostnames('server, app, db');

        $this->assertSame(count($hosts), 3);
        $this->assertSame('server', $hosts[0]->getHostname());
        $this->assertSame('app', $hosts[1]->getHostname());
        $this->assertSame('db', $hosts[2]->getHostname());
    }

    public function testReturnEmptyArrayOfHostsUsingGetByRolesIfNoRolesDefined()
    {
        $roles = ['server'];
        $hostCollection = new HostCollection();
        $hostCollection->set('server', new Host('server'));
        $hostSelector = new HostSelector($hostCollection);

        $this->assertEmpty($hostSelector->getByRoles($roles));
    }

    public function testReturnHostsArrayUsingGetByRoles()
    {
        $roles = "role1, role2";
        $host = new  Host('server');
        $host->roles("role1");
        $host->roles("role2");
        $hostCollection = new HostCollection();
        $hostCollection->set('server', $host);
        $hostSelector = new HostSelector($hostCollection);

        $this->assertNotEmpty($hostSelector->getByRoles($roles));
    }
}