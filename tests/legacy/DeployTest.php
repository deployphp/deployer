<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class DeployTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/deploy.php';

    public function testDeploy()
    {
        $display = $this->dep(self::RECIPE, 'deploy');

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertDirectoryExists($deployPath . '/.dep');
            self::assertDirectoryExists($deployPath . '/releases');
            self::assertDirectoryExists($deployPath . '/shared');
            self::assertDirectoryExists($deployPath . '/current');
            self::assertDirectoryExists($deployPath . '/current/');
            self::assertFileExists($deployPath . '/current/README.md');
            self::assertDirectoryExists($deployPath . '/current/storage/logs');
            self::assertDirectoryExists($deployPath . '/current/storage/db');
            self::assertDirectoryExists($deployPath . '/shared/storage/logs');
            self::assertDirectoryExists($deployPath . '/shared/storage/db');
            self::assertFileExists($deployPath . '/shared/uploads/poem.txt');
            self::assertFileExists($deployPath . '/shared/.env');
            self::assertFileExists($deployPath . '/current/config/test.yaml');
            self::assertFileExists($deployPath . '/shared/config/test.yaml');
            self::assertEquals(1, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    public function testDeploySelectHosts()
    {
        $this->init(self::RECIPE);
        $this->tester->setInputs(['0,1']);
        $this->tester->run(['deploy', '-f' => self::RECIPE, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_NORMAL,
            'interactive' => true,
        ]);
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
    }

    public function testKeepReleases()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->dep(self::RECIPE, 'deploy');
            self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
        }

        for ($i = 0; $i < 6; $i++) {
            $this->dep(self::RECIPE, 'deploy:fail');
            self::assertEquals(1, $this->tester->getStatusCode(), $this->tester->getDisplay());
        }

        for ($i = 0; $i < 3; $i++) {
            $this->dep(self::RECIPE, 'deploy');
            self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
        }

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals(3, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    /**
     * @depends testKeepReleases
     */
    public function testRollback()
    {
        $this->dep(self::RECIPE, 'rollback');

        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals(3, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    public function testFail()
    {
        $this->dep(self::RECIPE, 'deploy:fail');

        $display = $this->tester->getDisplay();
        self::assertEquals(1, $this->tester->getStatusCode(), $display);

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals('ok', exec("cd $deployPath && [ -f .dep/deploy.lock ] || echo ok"), 'fail hooks deploy:unlock did not run');
        }
    }

    /**
     * @depends testFail
     */
    public function testCleanup()
    {
        $this->dep(self::RECIPE, 'deploy:cleanup');

        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertFileDoesNotExist($deployPath . '/release');
        }
    }

    public function testIsUnlockedExitsWithOneWhenDeployIsLocked()
    {
        $this->dep(self::RECIPE, 'deploy:lock');
        $this->dep(self::RECIPE, 'deploy:is_locked');
        $display = $this->tester->getDisplay();

        self::assertStringContainsString('Deploy is locked by ', $display);
        self::assertSame(1, $this->tester->getStatusCode());
    }

    public function testIsUnlockedExitsWithZeroWhenDeployIsNotLocked()
    {
        $this->dep(self::RECIPE, 'deploy:unlock');
        $this->dep(self::RECIPE, 'deploy:is_locked');
        $display = $this->tester->getDisplay();

        self::assertStringContainsString('Deploy is unlocked.', $display);
        self::assertSame(0, $this->tester->getStatusCode());
    }
}
