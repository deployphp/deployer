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
class TYPO3Template extends Template
{
    /**
     * Get content of template.
     *
     * @param array $params
     *
     * @return string
     */
    protected function getTemplateContent($params)
    {
        return <<<PHP
<?php
namespace Deployer;

require 'recipe/typo3.php';

// Introduction
// This recipe is based on the "common" recipe, but has pre configured the 
// TYPO3 relevant settings for shared files and folders
// The TYPO3 recipe provides a special setting, the typo3_webroot which
// defines the DocumentRoot folder, default is "Web".
// Define all other settings like within a "common" recipe.

// Configuration
// #############
// Override DocumentRoot here
// set('typo3_webroot', 'Web');

// Hosts
// host('project.com')
//     ->stage('production')
//     ->set('deploy_path', '/var/www/project.com');

// host('beta.project.com')
//     ->stage('beta')
//     ->set('deploy_path', '/var/www/project.com');
PHP;
    }
}
