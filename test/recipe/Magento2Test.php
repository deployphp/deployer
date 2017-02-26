<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Magento2Test extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/magento2.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = [
            'var/log',
            'var/backups',
            'pub/media',
        ];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = [
            'app/etc/env.php',
            'var/.maintenance.ip',
        ];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = [
            'var',
            'pub/static',
            'pub/media',
        ];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertEnvParameterEquals('clear_paths', [
            'var/generation/*',
            'var/cache/*',
        ]);

        $this->assertTaskIsDefined([
            'magento:enable',
            'magento:compile',
            'magento:deploy:assets',
            'magento:maintenance:enable',
            'magento:maintenance:disable',
            'magento:upgrade:db',
            'magento:cache:flush',

        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy:magento', 7);
        $this->assertGroupTaskStepsNumberEquals('deploy', 13);
    }
}
