<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a node.js deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 */
class NodeJsTemplate extends Template
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

require 'recipe/common.php';

// Set configurations
set('repository', 'git@domain.com:username/repository.git');
set('shared_files', []);
set('shared_dirs', []);
set('writable_dirs', []);

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
 * Install npm packages.
 */
task('deploy:npm', function () {
    \$releases = env('releases_list');

    if (isset(\$releases[1])) {
        if(run("if [ -d {{deploy_path}}/releases/{\$releases[1]}/node_modules ]; then echo 'true'; fi")->toBool()) {
            run("cp --recursive {{deploy_path}}/releases/{\$releases[1]}/node_modules {{release_path}}");
        }
    }

    run("cd {{release_path}} && npm install");
});

/**
 * Restart server on success deploy.
 */
task('pm2:restart', function () {
    run("pm2 restart eightd");
})->desc('Restart pm2 service');

after('success', 'pm2:restart');

/**
 * Main task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:npm',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');

after('deploy', 'success');
PHP;
    }
}
