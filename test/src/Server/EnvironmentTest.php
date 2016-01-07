<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException
     * @expectedExceptionMessage "Key `no_name` is invalid"
     */
    public function testGetDefault()
    {
        Environment::getDefault('no_name');
    }

    public function testEnvironment()
    {
        Environment::setDefault('default', 'default');
        Environment::setDefault('callback', function () {
            return 'callback';
        });
        $env = new Environment();

        $env->set('int', 42);
        $env->set('string', 'value');
        $env->set('array', [1, 'two']);
        $env->set('parse', 'is {{int}}');

        $this->assertEquals(42, $env->get('int'));
        $this->assertEquals('value', $env->get('string'));
        $this->assertEquals([1, 'two'], $env->get('array'));
        $this->assertEquals('default', $env->get('no', 'default'));
        $this->assertEquals('default', $env->get('default'));
        $this->assertEquals('default', Environment::getDefault('default'));
        $this->assertEquals('callback', $env->get('callback'));
        $this->assertEquals('is 42', $env->get('parse'));

        $env->set('int', 11);
        $this->assertEquals('is 11', $env->get('parse'));

        $this->setExpectedException('RuntimeException', 'Environment parameter `so` does not exists.');
        $env->get('so');
    }

    /**
     * Protected env parameters cannot be changed.
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
     * Elements of a protected env array parameter cannot be changed by the dot
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
}
