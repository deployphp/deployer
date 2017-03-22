<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class YiiTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/yii.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['runtime'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedWritableDirs = ['runtime'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertGroupTaskStepsNumberEquals('deploy', 9);
    }
}
