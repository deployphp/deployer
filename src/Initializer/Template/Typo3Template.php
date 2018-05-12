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
class Typo3Template extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'typo3';
    }
}
