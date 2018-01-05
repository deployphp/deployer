<?php
/* (c) github.com/zorn-v
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Symfony flex deployer configuration.
 *
 * @author zorn-v
 * @codeCoverageIgnore
 */
class SymfonyFlexTemplate extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'symfony-flex';
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtraContent()
    {
        return <<<PHP

// Migrate database before symlink new release.

before('deploy:symlink', 'database:migrate');

//If you don't use DotEnv in prod just wipe it out
task('clear:env', function () {
    //DotEnt does not execute if APP_ENV is set, but it required for `composer install`
    \$env = get('env');
    unset(\$env['APP_ENV']);
    set('env', \$env);
})->setPrivate();
after('deploy:vendors', 'clear:env');
PHP;
    }
}
