<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class FileLoaderTest extends TestCase
{
    /**
     * @var Host[]
     */
    private $hosts;

    public function testLoad()
    {
        $this->hosts = (new FileLoader())
            ->load(__DIR__ . '/inventory.yml')
            ->getHosts();


        // .base does not exists
        self::assertNull($this->getHost('.base'), 'Hidden hosts exists in inventory');

        // foo extends .base
        $foo = $this->getHost('foo');
        self::assertInstanceOf(Host::class, $foo);
        self::assertEquals(['a', 'b', 'c'], $foo->get('roles'));

        // local is Localhost
        $local = $this->getHost('local');
        self::assertInstanceOf(Localhost::class, $local);
        self::assertEquals('/var/local', $local->get('deploy_to'));

        // bar configured properly
        $bar = $this->getHost('bar');
        self::assertEquals('bar', $bar->alias());
        self::assertEquals('bar.com', $bar->hostname());
        self::assertEquals('user', $bar->user());
        self::assertEquals(22, $bar->port());
        self::assertEquals('configFile', $bar->configFile());
        self::assertEquals('identityFile', $bar->identityFile());
        self::assertTrue($bar->forwardAgent());
        self::assertFalse($bar->sshMultiplexing());
        self::assertEquals('param', $bar->get('param'));
        self::assertEquals(
            '-f -A -someFlag value -p 22 -F configFile -i identityFile -o Option=Value',
            $bar->getSshArguments()->getCliArguments()
        );

        $db1 = $this->getHost('db1.deployer.org');
        self::assertEquals('db1.deployer.org', $db1->alias());
        $db2 = $this->getHost('db2.deployer.org');
        self::assertEquals('db2.deployer.org', $db2->alias());
    }

    /**
     * @param $name
     * @return Host|null
     */
    private function getHost($name)
    {
        foreach ($this->hosts as $host) {
            if ($host->alias() === $name) {
                return $host;
            }
        }
        return null;
    }
}
