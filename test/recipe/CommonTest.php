<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Helper\RecipeTester;

class CommonTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/common.php';

        // Override update code task to use local copy.
        \Deployer\task('deploy:update_code', function () {
            \Deployer\runLocally('cp -R ' . __DIR__ . '/../fixture/app/. {{release_path}}');
        });
    }

    public function testPrepare()
    {
        $this->exec('deploy:prepare');

        $this->assertFileExists($this->getEnv('releases_path'));
        $this->assertFileExists($this->getEnv('shared_path'));
    }

    public function testRelease()
    {
        $this->exec('deploy:release');

        $this->assertFileExists($this->getEnv('release_path'));
    }

    public function testReleaseSymlink()
    {
        $removedDirectory = $this->getEnv('deploy_path') . '/directory';
        $releaseSymlink = $this->getEnv('release_path');

        mkdir($removedDirectory);
        unlink($releaseSymlink);
        symlink($removedDirectory, $releaseSymlink);
        rmdir($removedDirectory);

        $this->exec('deploy:release');

        $this->assertFileExists($releaseSymlink);
        $this->assertTrue(is_dir(readlink($releaseSymlink)));
    }

    public function testUpdateCode()
    {
        \Deployer\set('repository', 'https://github.com/deployphp/test.git');

        $this->exec('deploy:update_code');

        $this->assertFileExists($this->getEnv('release_path') . '/README.md');
    }

    public function testShared()
    {
        \Deployer\set('shared_dirs', ['app/logs']);
        \Deployer\set('shared_files', ['app/config/parameters.yml']);

        $this->exec('deploy:shared');

        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/app/logs'),
            $this->getEnv('shared_path') . '/app/logs'
        );
        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/app/config/parameters.yml'),
            $this->getEnv('shared_path') . '/app/config/parameters.yml'
        );

        $this->assertTrue(is_dir($this->getEnv('shared_path') . '/app/logs'));
        $this->assertFileExists($this->getEnv('shared_path') . '/app/config/parameters.yml');
    }

    public function testWriteable()
    {
        \Deployer\set('writable_mode', 'chmod');
        \Deployer\set('writable_chmod_mod', '0777');
        \Deployer\set('writable_dirs', ['app/cache', 'app/logs']);
        \Deployer\set('writable_use_sudo', false);

        $this->exec('deploy:writable');

        $this->assertTrue(is_writable($this->getEnv('release_path') . '/app/cache'));
        $this->assertTrue(is_writable($this->getEnv('release_path') . '/app/logs'));
    }

    public function testVendor()
    {
        $this->exec('deploy:vendors');

        $this->assertFileExists($this->getEnv('release_path') . '/vendor/autoload.php');
    }

    public function testSymlink()
    {
        $this->exec('deploy:symlink');

        $this->assertTrue(realpath($this->getEnv('current_path')) !== false);
        clearstatcache($this->getEnv('release_path'));
        $this->assertFalse(realpath($this->getEnv('release_path')) !== false, 'Symlink to release directory must gone after deploy:symlink.');
    }

    public function testCurrent()
    {
        $result = $this->exec('current');

        $this->assertContains('2', $result);
    }
}
