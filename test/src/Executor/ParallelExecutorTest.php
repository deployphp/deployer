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
     * @var string
     */
    private $recipeFile;

    /**
     * Load or describe recipe.
     *
     * @return void
     */
    protected function loadRecipe()
    {
        include $this->recipeFile = __DIR__ . '/../../fixture/recipe.php';
    }

    public function testParallel()
    {
        define('DEPLOYER_BIN', __DIR__ . '/../../../bin/dep');
        
        $display = $this->exec('test', ['--parallel' => true, '--file' => $this->recipeFile]);

        $this->assertContains('Ok', $display);
    }
}
 