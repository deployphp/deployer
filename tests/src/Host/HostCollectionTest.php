<?php

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class HostCollectionTest extends TestCase
{
    public function testFindOneByAlias(): HostCollection
    {
        $hosts = [];
        $hosts[] = new Host('host_1');
        $hosts[] = (new Host('host_2'))->set('alias', 'Aliased_host_2');

        $hostCollection = new HostCollection();
        $hostsByAlias = [];
        foreach ($hosts as $host) {
            $hostCollection->set($host->getHostname(), $host);
            $hostsByAlias[$host->getAlias()] = $host;
        }

        foreach ($hostsByAlias as $alias => $host) {
            $this->assertEquals($host, $hostCollection->findOneByAlias($alias));
        }

        return $hostCollection;
    }

    /**
     * @depends testFindOneByAlias
     */
    public function testFindOneByAliasException(HostCollection $hostCollection): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $hostCollection->findOneByAlias('unexpected');
    }
}
