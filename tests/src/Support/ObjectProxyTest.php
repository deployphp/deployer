<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

class ObjectProxyTest extends TestCase
{
    public function testObjectProxy()
    {
        $mock = self::getMockBuilder('stdClass')
            ->addMethods(['foo'])
            ->getMock();
        $mock
            ->expects(self::once())
            ->method('foo')
            ->with('a', 'b');

        $proxy = new ObjectProxy([$mock]);
        $proxy->foo('a', 'b');
    }
}
