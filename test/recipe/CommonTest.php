<?php
/* (c) Anton Medvedev <anton@elfet.ru>
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
    }

    public function testPrepare()
    {
        $this->exec('deploy:prepare');

        $this->assertFileExists(self::$deployPath . '/releases');
        $this->assertFileExists(self::$deployPath . '/shared');
    }

    public function testRelease()
    {
        $this->exec('deploy:release');

        $this->assertFileExists(self::$deployPath . '/release');
    }

    public function testUpdateCode()
    {
        set('repository', 'git@github.com:deployphp/test.git');

        $this->exec('deploy:update_code');

        $this->assertFileExists($this->getEnv('release_path') . '/README.md');
    }

    public function testShared()
    {
        set('shared_dirs', ['app/logs']);
        set('shared_files', ['app/config/parameters.yml']);

        $this->exec('deploy:shared');

        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/app/logs'),
            $this->getEnv('deploy_path') . '/shared/app/logs'
        );
        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/app/config/parameters.yml'),
            $this->getEnv('deploy_path') . '/shared/app/config/parameters.yml'
        );

        $this->assertTrue(is_dir($this->getEnv('deploy_path') . '/shared/app/logs'));
        $this->assertFileExists($this->getEnv('deploy_path') . '/shared/app/config/parameters.yml');
    }

    public function testWriteable()
    {
        $this->exec('deploy:writeable');
    }

    public function testVendor()
    {
        $this->exec('deploy:vendors');

        $this->assertFileExists($this->getEnv('release_path') . '/vendor/autoload.php');
    }
}
