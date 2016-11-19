<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

abstract class FrameworkTemplate extends Template
{
    /**
     * {@inheritDoc}
     */
    protected function getTemplateContent()
    {
        return <<<PHP
<?php
namespace Deployer;
require 'recipe/{$this->getRecipe()}.php';

// Configuration

set('repository', 'git@domain.com:username/repository.git');

add('shared_files', []);
add('shared_dirs', []);

add('writable_dirs', []);

// Servers

server('production', 'domain.com')
    ->user('username')
    ->identityFile()
    ->set('deploy_path', '/var/www/domain.com');


// Tasks

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});
after('deploy:symlink', 'php-fpm:restart');
{$this->getExtraContent()}
PHP;
    }

    abstract protected function getRecipe();

    protected function getExtraContent()
    {
        return '';
    }
}
