<?php

namespace Deployer\Selector;

use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use PHPUnit\Framework\TestCase;

class SelectorTest extends TestCase
{
    public function testSelectHosts()
    {
        $prod = (new Host('prod.domain.com'))->set('labels', ['stage' => 'prod']);
        $front = (new Host('prod.domain.com/front'))->set('labels', ['stage' => 'prod', 'tier' => 'frontend']);
        $beta = (new Host('beta.domain.com'))->set('labels', ['stage' => 'beta']);
        $dev = (new Host('dev'))->set('labels', ['stage' => 'dev']);
        $allHosts = [$prod, $front, $beta, $dev];

        $hosts = new HostCollection();
        foreach ($allHosts as $host) {
            $hosts->set($host->getAlias(), $host);
        }
        $selector = new Selector($hosts);
        self::assertEquals($allHosts, $selector->select('all'));
        self::assertEquals([$prod, $front], $selector->select('stage=prod'));
        self::assertEquals([$front], $selector->select('stage=prod & tier=frontend'));
        self::assertEquals([$front, $beta], $selector->select('prod.domain.com/front, stage=beta'));
        self::assertEquals([$prod, $beta, $dev], $selector->select('all & tier != frontend'));
    }
}
