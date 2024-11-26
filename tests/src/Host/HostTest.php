<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use Deployer\Configuration\Configuration;
use PHPUnit\Framework\TestCase;

class HostTest extends TestCase
{
    public function testHost()
    {
        $host = new Host('host');
        $host
            ->setHostname('hostname')
            ->setRemoteUser('remote_user')
            ->setPort(22)
            ->setConfigFile('~/.ssh/config')
            ->setIdentityFile('~/.ssh/id_rsa')
            ->setForwardAgent(true)
            ->setSshMultiplexing(true);

        self::assertEquals('host', $host->getAlias());
        self::assertStringContainsString('host', $host->getTag());
        self::assertEquals('hostname', $host->getHostname());
        self::assertEquals('remote_user', $host->getRemoteUser());
        self::assertEquals(22, $host->getPort());
        self::assertEquals('~/.ssh/config', $host->getConfigFile());
        self::assertEquals('~/.ssh/id_rsa', $host->getIdentityFile());
        self::assertEquals(true, $host->getForwardAgent());
        self::assertEquals(true, $host->getSshMultiplexing());
    }

    public function testConfigurationAccessor()
    {
        $host = new Host('host');
        $host
            ->set('roles', ['db', 'app'])
            ->set('key', 'value')
            ->set('array', [1])
            ->add('array', [2]);

        self::assertEquals(['db', 'app'], $host->get('roles'));
        self::assertEquals('value', $host->get('key'));
        self::assertEquals([1, 2], $host->get('array'));
    }

    public function testHostAlias()
    {
        $host = new Host('host/alias');
        self::assertEquals('host/alias', $host->getAlias());
        self::assertEquals('host', $host->getHostname());
    }

    public function testHostWithParams()
    {
        $host = new Host('host');
        $value = 'new_value';
        $host
            ->set('env', $value)
            ->set('identity_file', '{{env}}');

        self::assertEquals($value, $host->getIdentityFile());
    }

    public function testHostWithUserFromConfig()
    {
        $parent = new Configuration();
        $parent->set("deploy_user", function () {
            return "test_user";
        });

        $host = new Host('host');
        $host->config()->bind($parent);
        $host
            ->setHostname('host')
            ->setRemoteUser('{{deploy_user}}')
            ->setPort(22);

        self::assertEquals('test_user@host', $host->connectionString());
    }
}
