<?php

/**
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

/**
 * Callable password getter test case
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class CallablePasswordGetterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base testing with use closure
     */
    public function testWithUseClosure()
    {
        $callablePasswordGetter = new CallablePasswordGetter(function ($host, $user) {
            return $host . $user;
        });

        $password = $callablePasswordGetter->getPassword('host', 'user');

        $this->assertEquals('hostuser', $password, 'Password is mismatch after getting.');
    }

    /**
     * Base testing with invalid callable
     *
     * @expectedException \InvalidArgumentException
     * @expectedException The first argument must be a callable, but "string" given.
     */
    public function testWithInvalidCallable()
    {
        new CallablePasswordGetter('foo-bar');
    }
}
