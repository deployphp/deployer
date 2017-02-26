<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Typo3Test extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/typo3.php';
    }

    public function testRecipe()
    {
        $this->assertEnvParameterEquals('typo3_webroot', 'Web');

        $expectedSharedDirs = [
            '{{typo3_webroot}}/fileadmin',
            '{{typo3_webroot}}/typo3temp',
            '{{typo3_webroot}}/uploads'
        ];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['{{typo3_webroot}}/.htaccess'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = [
            '{{typo3_webroot}}/fileadmin',
            '{{typo3_webroot}}/typo3temp',
            '{{typo3_webroot}}/typo3conf',
            '{{typo3_webroot}}/uploads'
        ];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertGroupTaskStepsNumberEquals('deploy', 10);
    }
}
