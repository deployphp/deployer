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
        $b->user('user', 'password');
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
            ->with(Configuration::AUTH_BY_PUBLIC_KEY)
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
        $b->pubKey();
    }

    public function testEnv()
    {
        $config = $this->getMockBuilder('Deployer\Server\Configuration')->disableOriginalConstructor()->getMock();
        $env = $this->getMock('Deployer\Server\Environment');
        $env->expects($this->once())
            ->method('set')
            ->with('name', 'value')
            ->will($this->returnSelf());

        $b = new Builder($config, $env);
        $b->env('name', 'value');
    }
}
