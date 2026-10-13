<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use spec\SpecTest;
use Symfony\Component\Console\Output\Output;

class MagentoThemeTest extends SpecTest
{
    public const RECIPE = __DIR__ . '/recipe/magento_theme.php';

    public function testDetectsWhichReleasesNeedThemeRegistration()
    {
        $this->createRelease('removed/release', ['Magento/blank', 'Acme/default']);
        $this->createRelease('removed/previous', ['Magento/blank', 'Acme/default', 'Acme/reset']);

        $this->createRelease('reparented/release', ['Acme/default'], 'Magento/blank');
        $this->createRelease('reparented/previous', ['Acme/default'], 'Acme/reset');

        $this->createRelease('unchanged/release', ['Magento/blank', 'Acme/default']);
        $this->createRelease('unchanged/previous', ['Magento/blank', 'Acme/default']);

        $this->createRelease('recorded/release', ['Magento/blank', 'Acme/default']);
        $this->createRelease('recorded/previous', ['Magento/blank']);

        $this->init(self::RECIPE);

        $this->runTask('magento:theme:fingerprint', 'recorded');
        self::assertFileExists(__TEMP_DIR__ . '/magento-theme/recorded/theme_fingerprint');

        $display = $this->runTask('test_theme_registration_needed', 'all');

        self::assertMatchesRegularExpression('/\[removed\] REGISTRATION NEEDED/', $display);
        self::assertMatchesRegularExpression('/\[reparented\] REGISTRATION NEEDED/', $display);
        self::assertMatchesRegularExpression('/\[unchanged\] REGISTRATION NOT NEEDED/', $display);

        // The themes this release registered are recorded, so the previous release, which has
        // one theme less, is no longer the thing to compare against.
        self::assertMatchesRegularExpression('/\[recorded\] REGISTRATION NOT NEEDED/', $display);
    }

    /**
     * @param list<string> $themes
     */
    private function createRelease(string $path, array $themes, ?string $parent = null): void
    {
        $release = __TEMP_DIR__ . '/magento-theme/' . $path;
        $parent = $parent === null ? '' : "<parent>$parent</parent>";

        mkdir($release . '/app/design/frontend', 0777, true);

        foreach ($themes as $theme) {
            mkdir($release . '/vendor/themes/' . $theme, 0777, true);
            file_put_contents(
                $release . '/vendor/themes/' . $theme . '/theme.xml',
                "<theme><title>$theme</title>$parent</theme>",
            );
        }
    }

    private function runTask(string $task, string $selector): string
    {
        $this->tester->run([
            $task,
            'selector' => $selector,
            '-f' => self::RECIPE,
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        return $display;
    }
}
