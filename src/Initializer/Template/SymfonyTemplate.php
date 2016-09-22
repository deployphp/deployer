<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Symfony deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 */
class SymfonyTemplate extends Template
{
    /**
     * {@inheritDoc}
     */
    protected function getTemplateContent()
    {
        $fullPath = realpath(implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            '..',
            '..',
            'recipe',
            'symfony.php'
        ]));
        return <<<PHP
<?php
/*
 * This file has been generated automatically.
 * Please change the configuration for correct use deploy.
 */
namespace Deployer;

// Change the full path to relative
require '{$fullPath}';

// Set configurations
set('repository', 'git@domain.com:username/repository.git');
set('shared_files', ['app/config/parameters.yml']);
// Add the `web/uploads` to shared and writable dirs if you are using upload directory!
set('shared_dirs', ['app/logs']);
set('writable_dirs', ['app/cache', 'app/logs']);
// Removable files after deploy
set('removable_files', ['web/app_*.php', 'web/config.php']);

env('enable_database_create', false);
env('use_database_migration_strategy', true);

// Configure servers
server('production', 'prod.domain.com')
    ->user('username')
    ->password()
    ->env('deploy_path', '/var/www/prod.domain.com');

server('beta', 'beta.domain.com')
    ->user('username')
    ->password()
    ->env('deploy_path', '/var/www/beta.domain.com');

server('jenkins', 'jenkins.domain.com')
    ->user('username')
    ->password()
    ->env('deploy_path', '/var/jenkins/build/')
    ->env('enable_database_create', true)
    ->env('interaction', false);

/**
 * Restart php-fpm on success deploy.
 */
task('php-fpm:restart', function () {
    // Attention: The user must have rights for restart service
    // Attention: the command "sudo /bin/systemctl restart php-fpm.service" used only on CentOS system
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo /bin/systemctl restart php-fpm.service');
})->desc('Restart PHP-FPM service');

after('success', 'php-fpm:restart');
PHP;
    }
}
