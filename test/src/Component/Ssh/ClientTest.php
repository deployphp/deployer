<?php declare(strict_types=1);
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Ssh;

use Deployer\Host\Host;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private function createHostMock($sshConfig)
    {
        /** @var MockObject|Host $host */
        $host = $this->createMock(Host::class);
        $host->method('getConfigFile')->willReturn($sshConfig);

        $host->method('has')->withAnyParameters()->willReturnCallback(static function ($parameter) {
            if ($parameter === 'config_file') {
                return true;
            }
            return false;
        });

        return $host;
    }

    public function testSshConfigDoesNotUseTilde(): void
    {
        $sshOptions = Client::connectionOptions($this->createHostMock('/some/path'));
        $this->assertStringContainsString('-F /some/path', $sshOptions);
        $this->assertStringNotContainsString('`realpath', $sshOptions);
    }

    public function testSshConfigWithTildeUsesRealpath(): void
    {
        $sshOptions = Client::connectionOptions($this->createHostMock('~/.ssh/config'));
        $this->assertStringContainsString('-F `realpath ~/.ssh/config`', $sshOptions);
    }
}
