<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class WordpressTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/wordpress.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['wp-content/uploads'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['wp-config.php'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = ['wp-content/uploads'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertGroupTaskStepsNumberEquals('deploy', 11);
    }
}
