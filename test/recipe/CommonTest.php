<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Deployer\Helper\RecipeTester;

class CommonTest extends RecipeTester
{
    protected function loadRecipe()
    {
        require __DIR__ . '/../../recipe/common.php';
    }

    public function testPrepare()
    {
        $this->exec('deploy:prepare');

        $this->assertFileExists(self::$deployPath . '/releases');
        $this->assertFileExists(self::$deployPath . '/shared');
    }

    public function testRelease()
    {
        $this->exec('deploy:release');

        $this->assertFileExists(self::$deployPath . '/release');
    }
}
