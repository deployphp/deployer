<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;

class ReleaseTest extends DepCase
{
    protected function load()
    {
        require DEPLOYER_FIXTURES . '/recipe/release.php';
    }

    protected function setUp()
    {
        self::$currentPath = self::$tmpPath . '/localhost';
    }

    public function testReleaseStandard()
    {
        $output = $this->start('deploy');
        self::assertContains('release_path', $output);
        self::assertNotContains('previous_release', $output);

        $output = $this->start('deploy');
        self::assertContains('release_path', $output);
        self::assertContains('previous_release', $output);

        $releasePath = $previousRelease = '';
        if (preg_match('/release_path (.*)/', $output, $matches)) {
            $releasePath = $matches[1];
        }
        if (preg_match('/previous_release (.*)/', $output, $matches)) {
            $previousRelease = $matches[1];
        }
        self::assertNotSame($releasePath, $previousRelease, "Param release_path shouldn't be equal to previous_release.");
    }

    public function testReleaseOverrideReleaseName()
    {
        self::cleanUp();

        $output = $this->start('deploy', ['-o' => ['release_name=a']]);
        self::assertContains('release_path', $output);
        self::assertNotContains('previous_release', $output);

        $output = $this->start('deploy', ['-o' => ['release_name=b']]);
        self::assertContains('release_path', $output);
        self::assertContains('previous_release', $output);

        $releasePath = $previousRelease = '';
        if (preg_match('/release_path (.*)/', $output, $matches)) {
            $releasePath = $matches[1];
        }
        if (preg_match('/previous_release (.*)/', $output, $matches)) {
            $previousRelease = $matches[1];
        }
        self::assertNotSame($releasePath, $previousRelease, "Param release_path shouldn't be equal to previous_release.");
    }
}
