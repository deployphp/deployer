<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Server;

use Deployer\DeployerTester;

class ConfigurationTest extends DeployerTester
{
    public function testUserAuth()
    {
        $config = new Configuration('main', 'host');
        $config->user('user');

        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals(Configuration::AUTH_BY_PASSWORD, $config->getAuthenticationMethod());
    }

    public function testConfigFileAuth()
    {
        $config = new Configuration('main', 'host');
        $config->user('user')->configFile('~/.ssh/config');

        $this->assertEquals(Configuration::AUTH_BY_CONFIG, $config->getAuthenticationMethod());
    }

    public function testPubKeyAuth()
    {
        $config = new Configuration('main', 'host');
        $config->user('user')->pubKey();

        $this->assertEquals(Configuration::AUTH_BY_PUBLIC_KEY, $config->getAuthenticationMethod());
        $this->assertEquals($_SERVER['HOME'] . '/.ssh/id_rsa.pub', $config->getPublicKey());
        $this->assertEquals($_SERVER['HOME'] . '/.ssh/id_rsa', $config->getPrivateKey());

        $this->assertEquals('', $config->getPassPhrase());

        $config->setPassPhrase(null);

        $this->assertEquals('password', $config->getPassPhrase());
    }
}
 