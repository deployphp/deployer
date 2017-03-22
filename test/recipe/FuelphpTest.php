<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Helper\RecipeTester;

class FuelphpTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/fuelphp.php';
    }

    public function testRecipe()
    {
        $expectedSharedDirs = ['fuel/app/cache', 'fuel/app/logs'];
        $this->assertEnvParameterEquals('shared_dirs', $expectedSharedDirs);

        $this->assertGroupTaskStepsNumberEquals('deploy', 10);
    }
}
