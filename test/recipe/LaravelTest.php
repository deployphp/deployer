<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class LaravelTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/laravel.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['storage'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['.env'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = [
            'bootstrap/cache',
            'storage',
            'storage/app',
            'storage/app/public',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertTaskIsDefined([
            'artisan:up',
            'artisan:down',
            'artisan:migrate',
            'artisan:migrate:rollback',
            'artisan:migrate:status',
            'artisan:db:seed',
            'artisan:cache:clear',
            'artisan:config:cache',
            'artisan:route:cache',
            'artisan:view:clear',
            'artisan:optimize',
            'artisan:queue:restart',
            'deploy:public_disk',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 15);
    }
}
