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
        return <<<PHP
<?php
/*
 * This file has been generated automatically.
 * Please change the configuration for correct use deploy.
 */

require 'recipe/symfony.php';

// Set configurations
set('repository', 'git@domain.com:username/repository.git');
set('shared_files', ['app/config/parameters.yml']);
set('shared_dirs', ['app/logs']);
set('writable_dirs', ['app/cache', 'app/logs']);
set('maintenance_template', 'app/Resources/views/maintenance.html');

// Configure servers
server('production', 'prod.domain.com')
    ->user('username')
    ->password()
    ->env('deploy_path', '/var/www/prod.domain.com');

server('beta', 'beta.domain.com')
    ->user('username')
    ->password()
    ->env('deploy_path', '/var/www/beta.domain.com');

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

/**
 * Maintenance mode. The database migration is a critical step, you need disable access the user to page.
 *
 * If you want to use the maintenance mode, follow the next steps:
 *  1. Create an `app/Resources/views/maintenance.html`. This file will see users during the maintenance.
 *  2. Configure the webserver. If `maintenance.html` exists in the document root, force show this file with 503 status!
 *
 *      Apache eg:
 *
 *      ErrorDocument 503 /maintenance.html
 *      RewriteEngine On
 *      RewriteCond %{REQUEST_URI} !\.(css|js|gif|jpg|png)$
 *      RewriteCond %{DOCUMENT_ROOT}/maintenance.html -f
 *      RewriteCond %{SCRIPT_FILENAME} !maintenance.html
 *      RewriteRule ^.*$  -  [redirect=503,last]
 *
 *      Nginx eg:
 *
 *      if (-f \$document_root/maintenance.html) {
 *        return 503;
 *      }
 *      error_page 503 @maintenance;
 *      location @maintenance {
 *        rewrite  ^(.*)$  /maintenance.html last;
 *        break;
 *      }
 *
 * You can create more maintenance templates. Lock the current page with custom template:
 *
 *  bin/dep maintenance:lock prod --maintenance-file="app/Resources/views/maintenances/hardware-upgrade.html"
 */
before('database:migrate', 'maintenance:lock');
after('rollback', 'maintenance:unlock');


/**
 * Attention: This command is only for for example. Please follow your own migrate strategy.
 * Attention: Commented by default.  
 * Migrate database before symlink new release.
 */
 
// before('deploy:symlink', 'database:migrate');
PHP;
    }
}
