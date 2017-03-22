<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class ComposerTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/composer.php';
    }

    public function testRecipe()
    {
        $this->assertGroupTaskStepsNumberEquals('deploy', 10);
    }
}
