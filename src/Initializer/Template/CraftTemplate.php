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
class CraftTemplate extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'craftcms';
    }

    protected function getExtraContent()
    {
        return <<<PHP

// Run migrations and sync project config before symlink new release.
// Craft should already be installed for this to work.
// before('deploy:symlink', 'craft:project_config:sync');
// before('deploy:symlink', 'craft:migrate:all');

PHP;
    }
}
