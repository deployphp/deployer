<?php

/**
 * (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

/**
 * Builder testing
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test set user for connection
     */
    public function testUser()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setUser')
            ->with('user')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->user('user');
    }

    /**
     * Test set password with
     */
    public function testPasswordSetScalar()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PASSWORD)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPassword')
            ->with('password')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->password('password');
    }

    /**
     * Test set password getter
     */
    public function testPasswordSetGetter()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');
        $password = $this->getMockForAbstractClass('Deployer\Server\Password\PasswordGetterInterface');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PASSWORD)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPassword')
            ->with($password)
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->password($password);
    }

    /**
     * Test set invalid password getter
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The password should be a string or PasswordGetterInterface instances, but "stdClass" given.
     */
    public function testPasswordWithInvalidObject()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');
        $password = (object)[];

        $b = new Builder($config, $env);
        $b->password($password);
    }

    /**
     * Test password with non scalar value
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The password should be a string or PasswordGetterInterface instances, but "array" given.
     */
    public function testPasswordWithNonScalarValue()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');
        $password = ['foo', 'bar'];

        $b = new Builder($config, $env);
        $b->password($password);
    }

    /**
     * Test password with null value
     */
    public function testPasswordWithNullValue()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PASSWORD)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPassword')
            ->with($this->isInstanceOf('Deployer\Server\Password\CallablePasswordGetter'))
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->password();
    }

    /**
     * Test set host and port
     */
    public function testHostAndPort()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setHost')
            ->with('localhost')
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPort')
            ->with(22)
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->host('localhost');
        $b->port(22);
    }

    /**
     * Test set configuration file for connection
     */
    public function testConfig()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_CONFIG)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setConfigFile')
            ->with('~/.config')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->configFile('~/.config');
    }

    /**
     * Test set pem file for connection
     */
    public function testPem()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PEM_FILE)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPemFile')
            ->with('~/.pem')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->pemFile('~/.pem');
    }

    /**
     * Test set public key for connection
     */
    public function testPublicKey()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_IDENTITY_FILE)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPublicKey')
            ->with('~/.ssh/id_rsa.pub')
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPrivateKey')
            ->with('~/.ssh/id_rsa')
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPassPhrase')
            ->with('pass-phrase')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->identityFile(null, null, 'pass-phrase');
    }

    /**
     * Test set public key for connection with use password getter for pass phrase
     */
    public function testPublicKeyWithNullPassPhrase()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_IDENTITY_FILE)
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPublicKey')
            ->with('~/.ssh/id_rsa.pub')
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPrivateKey')
            ->with('~/.ssh/id_rsa')
            ->will($this->returnSelf());

        $config->expects($this->once())
            ->method('setPassPhrase')
            ->with($this->isInstanceOf('Deployer\Server\Password\CallablePasswordGetter'))
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->identityFile(null, null, null);
    }

    /**
     * Test set environment variable
     */
    public function testEnv()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');

        // Configuring stubs
        $config
            ->method('getName')
            ->will($this->returnValue('test-name'));

        $config
            ->method('getHost')
            ->will($this->returnValue('test-host'));

        $config
            ->method('getPort')
            ->will($this->returnValue(22));

        // The Builder class should create the server env variable.
        $env->expects($this->at(0))
            ->method('setAsProtected')
            ->with('server', [
                'name' => 'test-name',
                'host' => 'test-host',
                'port' => 22,
            ])
            ->will($this->returnSelf());

        // The `env` method of the Builder class should internally call the
        // Environment's `set` method.
        $env->expects($this->at(1))
            ->method('set')
            ->with('name', 'value')
            ->will($this->returnSelf());
        $b = new Builder($config, $env);
        $b->env('name', 'value');
    }

    /**
     * Test use forward agent for connection
     */
    public function testForwardAgent()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_AGENT)
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->forwardAgent();
    }
}
