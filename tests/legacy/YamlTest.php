<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class YamlTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/deploy.yaml';

    public function testDeploy()
    {
        $this->init(self::RECIPE);
        $this->deployer->config->set('repository', __REPOSITORY__);
        $this->tester->run([
            'deploy',
            'selector' => 'all',
            '-f' => self::RECIPE,
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
            'interactive' => false,
        ]);

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
            self::assertEquals(1, intval(`cd $deployPath && ls -1 releases | wc -l`));
        }
    }
}
