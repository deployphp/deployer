<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class DeployTest extends TestCase
{
    protected function load()
    {
        require __DIR__ . '/deploy.php';
    }

    public function testDeploy()
    {
        $output = $this->start('deploy', ['localhost', '-vvv']);

        self::assertContains('Successfully deployed!', $output);
        self::assertDirectoryExists(self::$deployPath . '/.dep');
        self::assertDirectoryExists(self::$deployPath . '/releases');
        self::assertDirectoryExists(self::$deployPath . '/shared');
        self::assertDirectoryExists(self::$deployPath . '/current');
        self::assertFileExists(self::$deployPath . '/current/composer.json');
        self::assertEquals(1, exec("ls -1 releases | wc -l"));
    }

    public function testKeepReleases()
    {
        $this->start('deploy', ['localhost']);
        $this->start('deploy', ['localhost']);
        $this->start('deploy', ['localhost']);
        $this->start('deploy', ['localhost']);

        $this->start('deploy', ['localhost']);
        exec('touch current/ok.txt');

        $this->start('deploy', ['localhost']);
        exec('touch current/fail.txt');

        self::assertEquals(5, exec("ls -1 releases | wc -l"));
    }

    /**
     * @depends testKeepReleases
     */
    public function testRollback()
    {
        $this->start('rollback', ['localhost']);

        self::assertEquals(4, exec("ls -1 releases | wc -l"));
        self::assertFileExists(self::$deployPath . '/current/ok.txt');
        self::assertFileNotExists(self::$deployPath . '/current/fail.txt');
    }
}
