<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    public function testExpand()
    {
        self::assertEquals(['h1', 'h2', 'h3'], Range::expand(['h[1:3]']));
        self::assertEquals(['h1', 'h2', 'ha'], Range::expand(['h[1:2]', 'ha']));
        self::assertEquals(['h0', 'h1'], Range::expand(['h[0:1]']));
        self::assertEquals(['h1'], Range::expand(['h[1:1]']));
        self::assertEquals(['ha', 'hb', 'hc', 'hd'], Range::expand(['h[a:d]']));

        $hostnames = Range::expand(['h[01:20]']);
        self::assertContains('h01', $hostnames);
        self::assertContains('h10', $hostnames);
        self::assertContains('h20', $hostnames);

        self::assertCount(100, Range::expand(['h[1:100]']));
        self::assertCount(26, Range::expand(['h[a:z]']));
    }
}
