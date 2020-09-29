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
    public function testDeploy()
    {
        $recipe = __DIR__ . '/deploy.php';
        $deployer = $this->init($recipe);

        $this->tester->run([
            'deploy',
            '-s' => 'all',
            '-f' => $recipe
        ], [
            'verbosity' => Output::VERBOSITY_NORMAL,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertEquals(1, substr_count($display, 'should be run only once'), $display);

        foreach ($deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertDirectoryExists($deployPath . '/.dep');
            self::assertDirectoryExists($deployPath . '/releases');
            self::assertDirectoryExists($deployPath . '/shared');
            self::assertDirectoryExists($deployPath . '/current');
            self::assertDirectoryExists($deployPath . '/current/');
            self::assertFileExists($deployPath . '/current/README.md');
            self::assertFileExists($deployPath . '/shared/uploads/poem.txt');
            self::assertFileExists($deployPath . '/shared/.env');
            self::assertEquals(1, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    public function testDeploySelectHosts()
    {
        $recipe = __DIR__ . '/deploy.php';
        $this->init($recipe);

        $this->tester->setInputs(['0,1']);
        $this->tester->run(['deploy', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_NORMAL,
            'interactive' => true,
        ]);
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
    }

    public function testKeepReleases()
    {
        for ($i = 0; $i < 3; $i++) {
            $recipe = __DIR__ . '/deploy.php';
            $deployer = $this->init($recipe);

            $this->tester->run(['deploy', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
                'verbosity' => Output::VERBOSITY_VERBOSE,
                'interactive' => false,
            ]);

            self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());
        }

        foreach ($deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals(3, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    /**
     * @depends testKeepReleases
     */
    public function testRollback()
    {
        $recipe = __DIR__ . '/deploy.php';
        $deployer = $this->init($recipe);

        $this->tester->run(['rollback', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        foreach ($deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals(2, intval(exec("cd $deployPath && ls -1 releases | wc -l")));
        }
    }

    public function testFail()
    {
        $recipe = __DIR__ . '/deploy.php';
        $deployer = $this->init($recipe);

        $this->tester->run(['deploy:fail', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(1, $this->tester->getStatusCode(), $display);

        foreach ($deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertEquals('ok', exec("cd $deployPath && [ -f .dep/deploy.lock ] || echo ok"), 'fail hooks deploy:unlock did not run');
        }
    }

    /**
     * @depends testFail
     */
    public function testCleanup()
    {
        $recipe = __DIR__ . '/deploy.php';
        $deployer = $this->init($recipe);

        $this->tester->run(['deploy:cleanup', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        foreach ($deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertFileNotExists($deployPath . '/release');
        }
    }

    public function testIsUnlockedExitsWithOneWhenDeployIsLocked()
    {
        $recipe = __DIR__ . '/deploy.php';

        $this->init($recipe);

        $this->tester->run(['deploy:lock', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $this->tester->run(['deploy:is-unlocked', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();

        self::assertStringContainsString('Deploy is currently locked.', $display);
        self::assertSame(1, $this->tester->getStatusCode());
    }

    public function testIsUnlockedExitsWithZeroWhenDeployIsNotLocked()
    {
        $recipe = __DIR__ . '/deploy.php';

        $this->init($recipe);

        $this->tester->run(['deploy:unlock', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $this->tester->run(['deploy:is-unlocked', '-s' => 'all', '-f' => $recipe, '-l' => 1], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();

        self::assertStringContainsString('Deploy is currently unlocked.', $display);
        self::assertSame(0, $this->tester->getStatusCode());
    }
}
