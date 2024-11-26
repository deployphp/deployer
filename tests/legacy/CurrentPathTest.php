<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class CurrentPathTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/deploy.php';

    public function testDeployWithDifferentCurrentPath()
    {
        $currentPath = __TEMP_DIR__ . '/prod/public_html';

        $this->init(self::RECIPE);
        $this->tester->run([
            'deploy',
            'selector' => 'prod',
            '-f' => self::RECIPE,
            '-o' => ['current_path=' . $currentPath],
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertFileExists($currentPath . '/README.md');
        self::assertFileExists($currentPath . '/config/test.yaml');
    }
}
