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

    public function testDeploy()
    {
        $output = $this->start('deploy', [], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);
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
        $this->start('deploy');
        $this->start('deploy');
        $this->start('deploy');
        $this->start('deploy');

        $this->start('deploy');
        exec('touch current/ok.txt');

        $this->start('deploy');
        exec('touch current/fail.txt');

        self::assertEquals(5, exec("ls -1 releases | wc -l"));
    }

    /**
     * @depends testKeepReleases
     */
    public function testRollback()
    {
        $this->start('rollback');

        self::assertEquals(4, exec("ls -1 releases | wc -l"));
        self::assertFileExists(self::$deployPath . '/current/ok.txt');
        self::assertFileNotExists(self::$deployPath . '/current/fail.txt');
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
        self::assertFileExists(self::$deployPath . '/current/ok.txt');
        self::assertFileNotExists(self::$deployPath . '/.dep/deploy.lock');

        $this->start('cleanup');
        self::assertEquals(4, exec("ls -1 releases | wc -l"));
        self::assertFileNotExists(self::$deployPath . '/release');
    }
}
