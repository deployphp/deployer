<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class MagentoTest extends RecipeTester
{
    protected function loadRecipe()
    {
        // Override update code task to use in after() callback.
        \Deployer\task('deploy:update_code', function () {
            // Just for setting up this task.
        });
        
        require __DIR__ . '/../../recipe/magento.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['var', 'media'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['app/etc/local.xml'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = ['var', 'media'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertTaskIsDefined([
            'deploy:cache:clear',
            'deploy:clear_version',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 11);
    }
}
