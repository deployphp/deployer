<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class HostTest extends TestCase
{
    public function testHost()
    {
        $host = new Host('host');
        $host
            ->set('hostname', 'hostname')
            ->set('user', 'user')
            ->set('port', 22)
            ->set('config_file', '~/.ssh/config')
            ->set('identity_file', '~/.ssh/id_rsa')
            ->set('forward_agent', true)
            ->set('ssh_multiplexing', true)
            ->sshOptions(['BatchMode' => 'yes', 'Compression' => 'yes']);

        self::assertEquals('host', $host->alias());
        self::assertEquals('hostname', $host->hostname());
        self::assertEquals('user', $host->user());
        self::assertEquals(22, $host->port());
        self::assertEquals('~/.ssh/config', $host->configFile());
        self::assertEquals('~/.ssh/id_rsa', $host->identityFile());
        self::assertEquals(true, $host->forwardAgent());
        self::assertEquals(true, $host->sshMultiplexing());
        self::assertStringContainsString(
            '-A -p 22 -F ~/.ssh/config -i ~/.ssh/id_rsa -o BatchMode=yes -o Compression=yes',
            $host->getSshArguments()->getCliArguments()
        );
    }

    public function testHostWithCustomPort()
    {
        $host = new Host('host');
        $host
            ->set('hostname','host')
            ->set('user','user')
            ->set('port',2222);

        self::assertEquals('-A -p 2222', $host->getSshArguments()->getCliArguments());
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
        self::assertEquals('host/alias', $host->alias());
        self::assertEquals('host', $host->hostname());
    }

    public function testHostWithParams()
    {
        $host = new Host('host');
        $value = 'new_value';
        $host
            ->set('env', $value)
            ->set('identity_file', '{{env}}');

        self::assertEquals($value, $host->identityFile());
    }
}
