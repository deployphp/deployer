<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server\Password;

use PHPUnit\Framework\TestCase;

/**
 * Testing simple password getter
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class SimplePasswordGetterTest extends TestCase
{
    /**
     * Base test
     */
    public function testBase()
    {
        $getter = new SimplePasswordGetter('foo-bar');

        $password = $getter->getPassword('host', 'user');

        $this->assertEquals('foo-bar', $password, 'Password mismatch.');
    }
}
