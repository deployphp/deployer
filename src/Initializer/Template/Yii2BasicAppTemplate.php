<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Initializer\Template;

/**
 * Generate a Yii2 app basic deployer configuration.
 *
 * @author Anton Medvedev <anton@medv.io>
 * @codeCoverageIgnore
 */
class Yii2BasicAppTemplate extends FrameworkTemplate
{
    protected function getRecipe()
    {
        return 'yii2-app-basic';
    }
}
