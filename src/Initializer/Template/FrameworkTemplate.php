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

// Project name
set('application', 'my_project');

// Project repository
set('repository', '{$params['repository']}');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', []);
{$stats}

// Hosts

host('project.com')
    ->set('deploy_path', '~/{{application}}');    
    
// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

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
