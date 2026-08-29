<?php

namespace Deployer;

use spec\SpecTest;
use Symfony\Component\Console\Output\Output;

class LocalArchiveTest extends SpecTest
{
    public const RECIPE = __DIR__ . '/recipe/local_archive.php';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        putenv('DEPLOYER_LOCAL_WORKER=false');
    }

    public static function tearDownAfterClass(): void
    {
        putenv('DEPLOYER_LOCAL_WORKER=true');
        parent::tearDownAfterClass();
    }

    public function testParallelLocalArchiveDeploy()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'deploy',
            'selector' => 'all',
            '-f' => self::RECIPE,
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');
            self::assertFileExists($deployPath . '/current/README.md', $display);
        }

        $gitRoot = trim((string) shell_exec('git rev-parse --show-toplevel'));
        self::assertFileDoesNotExist($gitRoot . '/archive.tar');
    }
}
