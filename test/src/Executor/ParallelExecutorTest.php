<?php
/* (c) Anton Medvedev <anton@medv.io>
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

    public static function setUpBeforeClass()
    {
        define('DEPLOYER_BIN', __DIR__ . '/../../../bin/dep');
    }

    public function testParallel()
    {
        $display = $this->exec('test', ['--parallel' => true, '--file' => $this->recipeFile]);

        $this->assertContains('Ok', $display);
        $this->assertNotContains('You should only see this for production', $display);
    }

    public function testParallelWithStage()
    {
        $display = $this->exec('test', ['--parallel' => true, '--file' => $this->recipeFile, 'stage' => 'production']);

        $this->assertContains('Ok', $display);
        $this->assertContains('[server3] You should only see this for production', $display);
        $this->assertContains('[server4] You should only see this for production', $display);
    }
}
