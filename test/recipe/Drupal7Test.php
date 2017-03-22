<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Drupal7Test extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/drupal7.php';
    }

    public function testRecipe()
    {
        $this->assertEquals('default', $this->getEnv('drupal_site'));

        $expectedSharedDirs = ['sites/{{drupal_site}}/files'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['sites/{{drupal_site}}/settings.php'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = ['sites/{{drupal_site}}/files'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertTaskIsDefined([
            'drupal:settings',
            'drupal:upload_files',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 8);
    }
}
