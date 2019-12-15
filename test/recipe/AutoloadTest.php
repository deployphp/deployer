<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class AutoloadTest extends DepCase
{
    protected function load()
    {
        Deployer::loadRecipe(DEPLOYER_FIXTURES . '/recipe/autoload.php');
    }

    protected function setUp(): void
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testRun()
    {
        $originStack = spl_autoload_functions();
        $this->reset();
        $newStack = spl_autoload_functions();

        self::assertEquals($originStack[0], $newStack[0]);
    }
}
