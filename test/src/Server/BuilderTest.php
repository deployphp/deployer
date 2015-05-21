<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testPassword()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PASSWORD)
            ->will($this->returnSelf());
        $config->expects($this->once())
            ->method('setUser')
            ->with('user')
            ->will($this->returnSelf());
        $config->expects($this->once())
            ->method('setPassword')
            ->with('password')
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->user('user');
        $b->password('password');
    }

    public function testHostAndPort()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('setHost')
            ->with('localhost')
            ->will($this->returnSelf());
        $config->expects($this->once())
            ->method('setPort')
            ->with(22)
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->host('localhost');
        $b->port(22);
    }

    public function testConfig()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_CONFIG)
            ->will($this->returnSelf());
        $config->expects($this->once())
            ->method('setConfigFile')
            ->with('~/.config')
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->configFile('~/.config');
    }

    public function testPem()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('setAuthenticationMethod')
            ->with(Configuration::AUTH_BY_PEM_FILE)
            ->will($this->returnSelf());
        $config->expects($this->once())
            ->method('setPemFile')
            ->with('~/.pem')
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->pemFile('~/.pem');
    }

    public function testPublicKey()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
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
            ->with('')
            ->will($this->returnSelf());
        $env = $this->getMock('Deployer\Server\Environment');

        $b = new Builder($config, $env);
        $b->identityFile();
    }

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
            ->method('set')
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
