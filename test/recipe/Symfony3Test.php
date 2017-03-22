<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class Symfony3Test extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/symfony3.php';
    }

    public function testRecipe()
    {
        $this->assertEnvParameterEquals('env', 'prod');

        $expectedSharedDirs = ['var/logs', 'var/sessions'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $expectedSharedFiles = ['app/config/parameters.yml'];
        $this->assertEnvParameterEquals('shared_files', $expectedSharedFiles);

        $expectedWritableDirs = ['var/cache', 'var/logs', 'var/sessions'];
        $this->assertEnvParameterEquals('writable_dirs', $expectedWritableDirs);

        $this->assertEnvParameterEquals('clear_paths', ['web/app_*.php', 'web/config.php']);
        $this->assertEnvParameterEquals('assets', ['web/css', 'web/images', 'web/js']);
        $this->assertEnvParameterEquals('dump_assets', false);
        $this->assertEnvParameterEquals('env_vars', 'SYMFONY_ENV=prod');
        $this->assertEnvParameterEquals('bin_dir', 'bin');
        $this->assertEnvParameterEquals('var_dir', 'var');
        $this->assertEnvParameterEquals('console_options', '--no-interaction --env=prod --no-debug');

        $this->assertTaskIsDefined([
            'deploy:create_cache_dir',
            'deploy:assets',
            'deploy:assets:install',
            'deploy:assetic:dump',
            'deploy:cache:warmup',
            'database:migrate',
        ]);
        $this->assertGroupTaskStepsNumberEquals('deploy', 17);
    }
}
