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
class SymfonyFlexTemplate extends SymfonyTemplate
{
    protected function getRecipe()
    {
        return 'symfony-flex';
    }
}
