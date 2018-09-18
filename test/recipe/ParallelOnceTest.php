<?php
/* (c) Marc Legay <marc@ru3.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class ParallelOnceTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/parallel.php';
    }

    protected function setUp()
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testOnce()
    {
        $output = $this->start('deploy', [
            '--parallel' => true,
            '--file' => DEPLOYER_FIXTURES . '/recipe/parallel.php'
        ], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG
        ]);

        self::assertFileExists(self::$currentPath . '/deployed-host1');
        self::assertFileNotExists(self::$currentPath . '/deployed-host2');
    }
}
