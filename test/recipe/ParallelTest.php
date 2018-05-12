<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class ParallelTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/deploy.php';
    }

    protected function setUp()
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testDeploy()
    {
        $output = $this->start('deploy', [
            '--parallel' => true,
            '--file' => DEPLOYER_FIXTURES . '/recipe/deploy.php'
        ], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG
        ]);

        self::assertContains('echo $0', $output, 'Missing output from worker.');
        self::assertContains('Successfully deployed!', $output);
        self::assertDirectoryExists(self::$currentPath . '/.dep');
        self::assertDirectoryExists(self::$currentPath . '/releases');
        self::assertDirectoryExists(self::$currentPath . '/shared');
        self::assertDirectoryExists(self::$currentPath . '/current');
        self::assertFileExists(self::$currentPath . '/current/composer.json');
        self::assertEquals(1, exec("ls -1 releases | wc -l"));
    }

    /**
     * @depends testDeploy
     */
    public function testFail()
    {
        self::expectException(Exception::class);
        $this->start('deploy_fail', [
            '--parallel' => true,
            '--file' => DEPLOYER_FIXTURES . '/recipe/deploy.php'
        ]);
    }
}
