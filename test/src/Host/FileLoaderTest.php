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
     * @var Host[]|Localhost[]
     */
    private $hosts;

    public function testLoad()
    {
        $this->hosts = (new FileLoader())
            ->load(__DIR__ . '/../../fixture/inventory.yml')
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
    }

    private function getHost($name)
    {
        foreach ($this->hosts as $host) {
            if ($host->getHostname() === $name) {
                return $host;
            }
        }
        return null;
    }
}
