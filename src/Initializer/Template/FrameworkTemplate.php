<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * @codeCoverageIgnore
 */
abstract class FrameworkTemplate extends Template
{
    /**
     * {@inheritDoc}
     */
    protected function getTemplateContent($params)
    {
        $stats = $params['allow_anonymous_stats']
            ? ''
            : "set('allow_anonymous_stats', false);";
        return <<<PHP
<?php
namespace Deployer;

require 'recipe/{$this->getRecipe()}.php';

// Configuration

set('repository', '{$params['repository']}');
set('git_tty', true); // [Optional] Allocate tty for git on first deployment
add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);
{$stats}

// Hosts

host('project.com')
    ->stage('production')
    ->set('deploy_path', '/var/www/project.com');
    
host('beta.project.com')
    ->stage('beta')
    ->set('deploy_path', '/var/www/project.com');  


// Tasks

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});
after('deploy:symlink', 'php-fpm:restart');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
{$this->getExtraContent()}
PHP;
    }

    abstract protected function getRecipe();

    protected function getExtraContent()
    {
        return '';
    }
}
