<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testEnvironment()
    {
        $env = new Environment();

        $env->set('int', 42);
        $env->set('string', 'value');
        $env->set('array', [1, 'two']);
        $env->set('parse', 'is {{int}}');
        $env->set('callback', function () {
            return 'callback';
        });

        $this->assertEquals(42, $env->get('int'));
        $this->assertEquals('value', $env->get('string'));
        $this->assertEquals([1, 'two'], $env->get('array'));
        $this->assertEquals('default', $env->get('no', 'default'));
        $this->assertEquals('callback', $env->get('callback'));
        $this->assertEquals('is 42', $env->get('parse'));

        $env->set('int', 11);
        $this->assertEquals('is 11', $env->get('parse'));

        $this->expectException('RuntimeException');
        $env->get('so');
    }

    /**
     * Protected set parameters cannot be changed.
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parameter `protected` cannot be set, because it's protected.
     */
    public function testProtection()
    {
        $env = new Environment();
        $env->setAsProtected('protected', 'value');
        $env->set('protected', 'value');
    }

    /**
     * Elements of a protected set array parameter cannot be changed by the dot
     * notation.
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parameter `protected.protected_key` cannot be set, because `protected` is protected.
     */
    public function testProtectionWithDots()
    {
        $env = new Environment();
        $env->setAsProtected('protected', [
            'protected_key' => 'value',
        ]);
        $env->set('protected.protected_key', 'some-other-value');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parameter `not_protected.protected` cannot be set, because it's protected.
     */
    public function testSubArrayProtection()
    {
        $env = new Environment();
        $env->set('not_protected', []);
        $env->setAsProtected('not_protected.protected', 'value');
        $env->set('not_protected.protected', 'value');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parameter `not_protected.protected.under_protection` cannot be set, because `not_protected.protected` is protected.
     */
    public function testUpperArrayProtection()
    {
        $env = new Environment();
        $env->set('not_protected', []);
        $env->setAsProtected('not_protected.protected', [
            'under_protection' => 'value',
        ]);

        // Since the `under_protection` key is under the protected
        // `not_protected.protected` parameter, the following operation is not
        // allowed.
        $env->set('not_protected.protected.under_protection', 'value');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parameter `not_protected` could not be set, because a protected parameter named `not_protected.protected` already exists.
     */
    public function testContainingProtectedParam()
    {
        $env = new Environment();
        $env->set('not_protected', []);
        $env->setAsProtected('not_protected.protected', 'value');

        // Ok
        $env->setAsProtected('protected', 'value');
        $env->setAsProtected('not', 'value');

        // Since `not_protected.protected` is a protected parameter, overwriting
        // the whole `not_protected` parameter is not allowed.
        $env->setAsProtected('not_protected', 'value');
    }

    public function testAddParams()
    {
        $env = new Environment();
        $env->set('config', [
            'one',
            'two' => 2,
            'nested' => [],
        ]);
        $env->add('config', [
            'two' => 20,
            'nested' => [
                'first',
            ],
        ]);
        $env->add('config', [
            'nested' => [
                'second',
            ],
        ]);

        $expected = [
            'one',
            'two' => 20,
            'nested' => [
                'first',
                'second',
            ],
        ];

        $this->assertEquals($expected, $env->get('config'));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Configuration parameter `config` isn't array.
     */
    public function testAddParamsToNotArray()
    {
        $env = new Environment();
        $env->set('config', 'option');
        $env->add('config', ['three']);
    }
}
