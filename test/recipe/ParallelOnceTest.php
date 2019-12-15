<?php
/* (c) Marc Legay <marc@ru3.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;

// skip
class ParallelOnceTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/parallel.php';
    }

    protected function setUp(): void
    {
        self::$currentPath = self::$tmpPath . '/localhost';
        self::markTestSkipped('TODO: This test should be fixed in future.');
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
        self::assertFileNotExists(self::$currentPath . '/deployed-host3');
        self::assertFileNotExists(self::$currentPath . '/deployed-host4');
    }

    public function testOnceWithLimit()
    {
        $output = $this->start('deploy', [
            '--parallel' => true,
            '--limit' => 2,
            '--file' => DEPLOYER_FIXTURES . '/recipe/parallel.php'
        ], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG
        ]);

        self::assertFileExists(self::$currentPath . '/deployed-host1');
        self::assertFileNotExists(self::$currentPath . '/deployed-host2');
        self::assertFileNotExists(self::$currentPath . '/deployed-host3');
        self::assertFileNotExists(self::$currentPath . '/deployed-host4');
    }
}
