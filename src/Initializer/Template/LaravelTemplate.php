<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Laravel deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 * @codeCoverageIgnore
 */
class LaravelTemplate extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'laravel';
    }

    protected function getExtraContent()
    {
        return <<<PHP

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

PHP;
    }
}
