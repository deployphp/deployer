<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

class ProxyTest extends TestCase
{
    public function testProxy()
    {
        $mock = self::getMockBuilder('stdClass')
            ->setMethods(['foo'])
            ->getMock();
        $mock
            ->expects(self::once())
            ->method('foo')
            ->with('a', 'b');

        $proxy = new Proxy([$mock]);
        $proxy->foo('a', 'b');
    }
}
