<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Yii2AppBasicTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/yii2-app-basic.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['runtime'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $this->assertTaskIsDefined('deploy:run_migrations');

        $this->assertGroupTaskStepsNumberEquals('deploy', 11);
    }
}
