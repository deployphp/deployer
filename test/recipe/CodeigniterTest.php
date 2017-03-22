<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class CodeigniterTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/codeigniter.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['application/cache', 'application/logs'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedWritableDirs = ['application/cache', 'application/logs'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertGroupTaskStepsNumberEquals('deploy', 10);
    }
}
