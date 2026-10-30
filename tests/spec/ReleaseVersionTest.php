<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use spec\SpecTest;

class ReleaseVersionTest extends SpecTest
{
    public const RECIPE = __DIR__ . '/recipe/release_version.php';
    private const TAG = 'v1.2.3';

    protected function setUp(): void
    {
        // Each test needs a fresh deploy path: the mirror in .dep/repo keeps tags deleted from the repository.
        self::cleanUp();
        mkdir(__TEMP_DIR__);
        // The current symlink may point to a release of a previous test: clear PHP realpath cache.
        clearstatcache(true);
    }

    protected function tearDown(): void
    {
        exec('git -C ' . __REPOSITORY__ . ' tag -d ' . self::TAG . ' 2>&1');
    }

    public function testVersionIsAbbreviatedCommitHashWithoutTag()
    {
        $this->depFile(self::RECIPE, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        $hash = trim(exec('git -C ' . __REPOSITORY__ . ' rev-parse HEAD'));
        $shortHash = trim(exec('git -C ' . __REPOSITORY__ . ' rev-parse --short HEAD'));
        foreach ($this->deployer->hosts as $host) {
            self::assertEquals($shortHash, trim(file_get_contents($host->get('deploy_path') . '/current/VERSION')));
            self::assertEquals($hash, trim(file_get_contents($host->get('deploy_path') . '/current/REVISION')));
        }
    }

    public function testVersionIsTagWhenTargetIsTagged()
    {
        exec('git -C ' . __REPOSITORY__ . ' tag ' . self::TAG);

        $this->depFile(self::RECIPE, 'deploy');
        self::assertEquals(0, $this->tester->getStatusCode(), $this->tester->getDisplay());

        $hash = trim(exec('git -C ' . __REPOSITORY__ . ' rev-parse HEAD'));
        foreach ($this->deployer->hosts as $host) {
            self::assertEquals(self::TAG, trim(file_get_contents($host->get('deploy_path') . '/current/VERSION')));
            self::assertEquals($hash, trim(file_get_contents($host->get('deploy_path') . '/current/REVISION')));
        }
    }
}
