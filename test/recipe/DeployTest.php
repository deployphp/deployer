<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class DeployTest
 * @package Deployer
 * @group deploy
 */
class DeployTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/deploy.php';
    }

    protected function setUp()
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testDeploy()
    {
        $output = $this->start('deploy', [], ['verbosity' => OutputInterface::VERBOSITY_DEBUG]);
        self::assertContains('Successfully deployed!', $output);
        self::assertDirectoryExists(self::$currentPath . '/.dep');
        self::assertDirectoryExists(self::$currentPath . '/releases');
        self::assertDirectoryExists(self::$currentPath . '/shared');
        self::assertDirectoryExists(self::$currentPath . '/current');
        self::assertFileExists(self::$currentPath . '/current/composer.json');
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

    public function testRecursive()
    {
        set('git_recursive', true);
        $this->start('deploy', [], []);

        self::assertFileNotExists(self::$currentPath . '/current/branch1');

        self::assertFileExists(self::$currentPath . '/current/module1/master');
        self::assertFileExists(self::$currentPath . '/current/module2/master');

        self::assertFileNotExists(self::$currentPath . '/current/module1/branch1');
        self::assertFileNotExists(self::$currentPath . '/current/module2/branch1');
    }

    public function testRecursiveBranch()
    {
        set('git_recursive', true);
        set('branch', 'branch1');

        $this->start('deploy', [], []);

        self::assertFileExists(self::$currentPath . '/current/branch1');

        self::assertFileExists(self::$currentPath . '/current/module1/master');
        self::assertFileExists(self::$currentPath . '/current/module2/master');

        self::assertFileExists(self::$currentPath . '/current/module1/branch1');
        self::assertFileNotExists(self::$currentPath . '/current/module2/branch1');
    }

    public function testRecursiveRevision()
    {
        set('git_recursive', true);

        $this->start('deploy', ['revision' => 'branch1'], []);

        self::assertFileExists(self::$currentPath . '/current/branch1');

        self::assertFileExists(self::$currentPath . '/current/module1/master');
        self::assertFileExists(self::$currentPath . '/current/module2/master');

        self::assertFileExists(self::$currentPath . '/current/module1/branch1');
        self::assertFileNotExists(self::$currentPath . '/current/module2/branch1');
    }

    public function testRecursiveDefinedModules_None()
    {
        set('git_recursive', true);
        set('git_submodules', false);

        $this->start('deploy', [], []);

        self::assertFileNotExists(self::$currentPath . '/current/module1/master');
        self::assertFileNotExists(self::$currentPath . '/current/module2/master');
    }

    public function testRecursiveDefinedModules_String()
    {
        set('git_recursive', true);
        set('git_submodules', 'module1');

        $this->start('deploy', [], []);

        self::assertFileExists(self::$currentPath . '/current/module1/master');
        self::assertFileNotExists(self::$currentPath . '/current/module2/master');
    }

    public function testRecursiveDefinedModules_Array()
    {
        set('git_recursive', true);
        set('git_submodules', ['module2']);

        $this->start('deploy', [], []);

        self::assertFileNotExists(self::$currentPath . '/current/module1/master');
        self::assertFileExists(self::$currentPath . '/current/module2/master');
    }

    public function testNotRecursive()
    {
        set('git_recursive', false);

        $this->start('deploy', [], []);

        self::assertFileNotExists(self::$currentPath . '/current/module1/master');
        self::assertFileNotExists(self::$currentPath . '/current/module2/master');
    }
}
