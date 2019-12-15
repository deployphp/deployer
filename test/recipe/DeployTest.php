<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DeployTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/deploy.php';
    }

    protected function setUp(): void
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testDeploy()
    {
        $output = $this->start('deploy', [], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);
        self::assertStringContainsString('Successfully deployed!', $output);
        self::assertDirectoryExists(self::$currentPath . '/.dep');
        self::assertDirectoryExists(self::$currentPath . '/releases');
        self::assertDirectoryExists(self::$currentPath . '/shared');
        self::assertDirectoryExists(self::$currentPath . '/current');
        self::assertFileExists(self::$currentPath . '/current/composer.json');
        self::assertFileExists(self::$currentPath . '/shared/public/media/.gitkeep');
        self::assertFileExists(self::$currentPath . '/shared/app/config/parameters.yml');
        self::assertEquals(1, exec("ls -1 releases | wc -l"));
    }

    public function testKeepReleases()
    {
        $this->start('deploy');
        $this->start('deploy');
        $this->start('deploy');
        $this->start('deploy');

        $this->start('deploy');
        exec('touch current/ok.txt');

        $this->start('deploy');
        exec('touch current/fail.txt');
        self::assertEquals(5, exec("ls -1 releases | wc -l"));

        // Make sure what after cleanup task same amount of releases a kept.
        $this->start('cleanup');
        self::assertEquals(5, exec("ls -1 releases | wc -l"));
    }

    /**
     * @depends testKeepReleases
     */
    public function testRollback()
    {
        $this->start('rollback');

        self::assertEquals(4, exec("ls -1 releases | wc -l"));
        self::assertFileExists(self::$currentPath . '/current/ok.txt');
        self::assertFileNotExists(self::$currentPath . '/current/fail.txt');
    }

    /**
     * @depends testRollback
     */
    public function testFail()
    {
        self::expectException(ProcessFailedException::class);
        $this->start('deploy_fail');
    }

    /**
     * @depends testFail
     */
    public function testAfterFail()
    {
        self::assertFileExists(self::$currentPath . '/current/ok.txt');
        self::assertFileNotExists(self::$currentPath . '/.dep/deploy.lock');

        $this->start('cleanup');
        self::assertEquals(5, exec("ls -1 releases | wc -l"));
        self::assertFileNotExists(self::$currentPath . '/release');
    }
}
