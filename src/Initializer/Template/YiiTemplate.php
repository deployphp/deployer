<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Yii deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 * @codeCoverageIgnore
 */
class YiiTemplate extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'yii';
    }
}
