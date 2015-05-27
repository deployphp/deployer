<?php

/**
 *  (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

/**
 * Configuration testing
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base test configuration
     */
    public function testConfiguration()
    {
        $c = new Configuration('name', 'localhost', 80);

        $this->assertEquals('name', $c->getName());
        $this->assertEquals('new_name', $c->setName('new_name')->getName());
        $this->assertEquals('localhost', $c->getHost());
        $this->assertEquals('new_localhost', $c->setHost('new_localhost')->getHost());
        $this->assertEquals(80, $c->getPort());
        $this->assertEquals(8080, $c->setPort(8080)->getPort());
        $this->assertEquals('user', $c->setUser('user')->getUser());
        $this->assertEquals('password', $c->setPassword('password')->getPassword());
        $this->assertEquals('password', $c->setPassPhrase('password')->getPassPhrase());
        $this->assertEquals(0, $c->setAuthenticationMethod(0)->getAuthenticationMethod());
        $this->assertEquals((isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '~') . '/.config', $c->setConfigFile('~/.config')->getConfigFile());
        $this->assertEquals((isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '~') . '/.pem', $c->setPemFile('~/.pem')->getPemFile());
        $this->assertEquals((isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '~') . '/.pub', $c->setPublicKey('~/.pub')->getPublicKey());
        $this->assertEquals((isset($_SERVER['HOME']) ? $_SERVER['HOME'] : '~') . '/.private', $c->setPrivateKey('~/.private')->getPrivateKey());
    }

    /**
     * Test get password with use PasswordGetter system
     */
    public function testGetPasswordWithUsePasswordGetter()
    {
        $configuration = new Configuration('name', 'localhost', 80);
        $configuration->setUser('user');

        $passwordGetter = $this->getMockForAbstractClass('Deployer\Server\Password\PasswordGetterInterface');
        $passwordGetter->expects($this->once())->method('getPassword')
            ->with('localhost', 'user')
            ->will($this->returnValue('some-password'));

        $configuration->setPassword($passwordGetter);

        $password = $configuration->getPassword();
        $this->assertEquals('some-password', $password, 'Password mismatch');
    }

    /**
     * Test get pass phrase with use PasswordGetter system
     */
    public function testGetPassPhraseWithUsePasswordGetter()
    {
        $configuration = new Configuration('name', 'localhost', 80);
        $configuration->setUser('user');

        $passwordGetter = $this->getMockForAbstractClass('Deployer\Server\Password\PasswordGetterInterface');
        $passwordGetter->expects($this->once())->method('getPassword')
            ->with('localhost', 'user')
            ->will($this->returnValue('some-pass-phrase'));

        $configuration->setPassPhrase($passwordGetter);

        $passPhrase = $configuration->getPassPhrase();
        $this->assertEquals('some-pass-phrase', $passPhrase, 'Pass phrases mismatch');
    }
}
