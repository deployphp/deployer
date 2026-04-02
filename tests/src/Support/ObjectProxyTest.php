<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

interface MockableWithFoo
{
    public function foo(string $a, string $b): void;
}

class ObjectProxyTest extends TestCase
{
    public function testObjectProxy()
    {
        $mock = $this->createMock(MockableWithFoo::class);
        $mock
            ->expects(self::once())
            ->method('foo')
            ->with('a', 'b');

        $proxy = new ObjectProxy([$mock]);
        $proxy->foo('a', 'b');
    }
}
