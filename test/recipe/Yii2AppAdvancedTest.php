<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Yii2AppAdvancedTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/yii2-app-advanced.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = [
            'frontend/runtime',
            'backend/runtime',
            'console/runtime',
        ];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = [
            'common/config/main-local.php',
            'common/config/params-local.php',
            'frontend/config/main-local.php',
            'frontend/config/params-local.php',
            'backend/config/main-local.php',
            'backend/config/params-local.php',
            'console/config/main-local.php',
            'console/config/params-local.php',
        ];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $this->assertTaskIsDefined([
            'deploy:init',
            'deploy:run_migrations',
        ]);

        $this->assertGroupTaskStepsNumberEquals('deploy', 12);
    }
}
