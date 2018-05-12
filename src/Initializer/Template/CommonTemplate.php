<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a common (base) deployer configuration
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 * @author Anton Medvedev <anton@medv.io>
 * @codeCoverageIgnore
 */
class CommonTemplate extends Template
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

require 'recipe/common.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', '{$params['repository']}');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
set('shared_files', []);
set('shared_dirs', []);

// Writable dirs by web server 
set('writable_dirs', []);
{$stats}

// Hosts

host('project.com')
    ->set('deploy_path', '~/{{application}}');    
    

// Tasks

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

PHP;
    }
}
