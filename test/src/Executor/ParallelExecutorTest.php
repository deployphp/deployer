<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Executor;

use Deployer\Helper\RecipeTester;

class ParallelExecutorTest extends RecipeTester
{
    /**
     * Load or describe recipe.
     *
     * @return void
     */
    protected function loadRecipe()
    {
        include __DIR__ . '/Fixture/recipe.php';
    }

    public function testParallel()
    {
        define('DEPLOYER_BIN', __DIR__ . '/../../../bin/dep');
        
        $display = $this->exec('test', ['--parallel' => true, '--file' => __DIR__ . '/Fixture/recipe.php']);

        $this->assertContains('Ok', $display);
    }
}
 