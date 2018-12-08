<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function testThrowExceptionIfStageOrHostnameNotFound()
    {
        $this->expectException(\Exception::class);

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

        for ($index = 0; $index < 100; $index++) {
            $hostCollection->set("host$index", new Host("host$index"));
        }

        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getHosts(null);

        $this->assertSame(count($hosts), 100);
    }

    public function testShouldThrowExceptionIfHostNameOrStageNotFound()
    {
        $this->expectException(\Exception::class);

        $host = new Host('app');
        $hostCollection = new HostCollection();
        $hostCollection->set('app', $host);
        $hostSelector = new HostSelector($hostCollection);
        $hostSelector->getHosts('stage');
    }

    public function testShouldReturnHostIfItHasStage()
    {
        $host = new Host('apps');
        $host->stage('stage');
        $hostCollection = new HostCollection();
        $hostCollection->set('apps', $host);
        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getHosts('stage');

        $this->assertSame(1, count($hosts));
    }

    public function testShouldReturnHostIfItHasHostnameEqualsStageName()
    {
        $host = new Host('apps');
        $hostCollection = new HostCollection();
        $hostCollection->set('apps', $host);
        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getHosts('apps');

        $this->assertSame(1, count($hosts));
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

    public function testReturnHostsByMultipleRolesUsingGetByRoles()
    {
        $roles = 'role1+role2';
        $roles2 = 'role1+role2,role3';
        $roles3 = 'role2,role1,role3+role2';

        $host = new  Host('server');
        $host->roles('role1', 'role2');
        $host2 = new Host('server2');
        $host2->roles('role1');
        $host3 = new Host('server3');
        $host3->roles('role2', 'role3');

        $hostCollection = new HostCollection();
        $hostCollection->set('server', $host);
        $hostCollection->set('server2', $host2);
        $hostCollection->set('server3', $host3);

        $hostSelector = new HostSelector($hostCollection);

        $this->assertEquals(['server' => $host], $hostSelector->getByRoles($roles));
        $this->assertEquals(['server' => $host, 'server3' => $host3], $hostSelector->getByRoles($roles2));
        $this->assertEquals(['server' => $host, 'server2' => $host2, 'server3' => $host3], $hostSelector->getByRoles($roles3));
    }
}
