<?php

namespace Deployer\Selector;

use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use PHPUnit\Framework\TestCase;

class SelectorTest extends TestCase
{
    public function testSelectHosts()
    {
        $prod = (new Host('prod'))->set('labels', ['stage' => 'prod']);
        $front = (new Host('prod/front'))->set('labels', ['stage' => 'prod', 'tier' => 'frontend']);
        $beta = (new Host('beta'))->set('labels', ['stage' => 'beta']);

        $hosts = new HostCollection();
        $hosts->set($prod->getAlias(), $prod);
        $hosts->set($front->getAlias(), $front);
        $hosts->set($beta->getAlias(), $beta);

        $selectedHosts = (new Selector($hosts))->selectHosts('stage=prod');
        self::assertEquals([$prod, $front], $selectedHosts);

        $selectedHosts = (new Selector($hosts))->selectHosts('stage=prod, tier=frontend');
        self::assertEquals([$front], $selectedHosts);

        $selectedHosts = (new Selector($hosts))->selectHosts('all');
        self::assertEquals([$prod, $front, $beta], $selectedHosts);

        $selectedHosts = (new Selector($hosts))->selectHosts('all, tier != frontend');
        self::assertEquals([$prod, $beta], $selectedHosts);
    }
}
