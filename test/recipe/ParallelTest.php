<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;

class ParallelTest extends DepCase
{
    const RECIPE = __DIR__ . '/deploy.php';

    protected function load()
    {
        require self::RECIPE;
    }

    public function testDeploy()
    {
        $output = $this->start('deploy',
            ['localhost', '--parallel' => true, '--file' => self::RECIPE],
            ['verbosity' => OutputInterface::VERBOSITY_DEBUG]
        );

        self::assertContains('Successfully deployed!', $output);
        self::assertDirectoryExists(self::$deployPath . '/.dep');
        self::assertDirectoryExists(self::$deployPath . '/releases');
        self::assertDirectoryExists(self::$deployPath . '/shared');
        self::assertDirectoryExists(self::$deployPath . '/current');
        self::assertFileExists(self::$deployPath . '/current/composer.json');
        self::assertEquals(1, exec("ls -1 releases | wc -l"));
    }
}
