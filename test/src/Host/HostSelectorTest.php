<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class HostSelectorTest extends TestCase
{
    public function testCanBeCreatedFromEmptyHostCollection()
    {
        $hostSelector = new HostSelector(new HostCollection());
        $classname = 'Deployer\Host\HostSelector';

        $this->assertInstanceOf($classname, $hostSelector);
    }

    public function testReturnCorrectSizeOfHostsArray()
    {
        $hostCollection = new HostCollection();

        for ($index = 0; $index < 100; $index++) {
            $hostCollection->set("host$index", new Host("host$index"));
        }

        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getAll(null);

        $this->assertSame(100, count($hosts));
    }

    public function testGetByHostnameReturnsArrayWithHostsAndCorrectLength()
    {
        $hostCollection = new HostCollection();
        $hostCollection->set('server', new Host('server'));
        $hostCollection->set('app', new Host('app'));
        $hostCollection->set('db', new Host('db'));
        $hostSelector = new HostSelector($hostCollection);
        $hosts = $hostSelector->getByHostnames(['server', 'app', 'db']);

        $this->assertSame(count($hosts), 3);
        $this->assertSame('server', $hosts[0]->alias());
        $this->assertSame('app', $hosts[1]->alias());
        $this->assertSame('db', $hosts[2]->alias());
    }

    public function testReturnEmptyArrayOfHostsUsingGetByRolesIfNoRolesDefined()
    {
        $roles = 'server';
        $hostCollection = new HostCollection();
        $hostCollection->set('server', new Host('server'));
        $hostSelector = new HostSelector($hostCollection);

        $this->assertEmpty($hostSelector->getByRoles($roles));
    }

    public function testReturnHostsArrayUsingGetByRoles()
    {
        $roles = 'role1,role2';
        $host = new Host('server');
        $host->set('roles', ['role1']);
        $host->set('roles', ['role2']);
        $hostCollection = new HostCollection();
        $hostCollection->set('server', $host);
        $hostSelector = new HostSelector($hostCollection);

        $this->assertNotEmpty($hostSelector->getByRoles($roles));
    }
}
