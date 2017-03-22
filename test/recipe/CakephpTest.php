<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class CakephpTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/cakephp.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['logs', 'tmp'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['config/app.php'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $this->assertTaskIsDefined([
            'deploy:init',
            'deploy:run_migrations',
        ]);

        $this->assertGroupTaskStepsNumberEquals('deploy', 12);
    }
}
