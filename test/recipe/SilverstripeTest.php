<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class SilverstripeTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/silverstripe.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['assets'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedWritableDirs = ['assets'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertTaskIsDefined([
            'silverstripe:build',
            'silverstripe:buildflush',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 12);
    }
}
