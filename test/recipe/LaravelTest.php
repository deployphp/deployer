<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Helper\RecipeTester;

class LaravelTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/laravel.php';
    }
    
    public function testDeploy()
    {
        set('repository', 'git@github.com:laravel/laravel.git');
        set('writable_use_sudo', false); // Using sudo in writable commands?

        $this->exec('deploy');

        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/storage'),
            $this->getEnv('deploy_path') . '/shared/storage'
        );
        $this->assertTrue(is_dir($this->getEnv('deploy_path') . '/shared/storage'));
        $this->assertTrue(is_writable($this->getEnv('deploy_path') . '/shared/storage'));

        $this->assertTrue(is_writable($this->getEnv('release_path') . '/vendor'));

        $this->assertEquals(
            realpath($this->getEnv('release_path') . '/.env'),
            $this->getEnv('deploy_path') . '/shared/.env'
        );
        $this->assertFileExists($this->getEnv('deploy_path') . '/shared/.env');
        $this->assertTrue(is_link($this->getEnv('release_path') . '/.env'));
    }
}