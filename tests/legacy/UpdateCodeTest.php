<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class UpdateCodeTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/update_code.php';

    public function testDeployWithDifferentUpdateCodeTask()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'deploy',
            'selector' => 'prod',
            '-f' => self::RECIPE,
        ], [
            'verbosity' => Output::VERBOSITY_VERBOSE,
        ]);

        $display = $this->tester->getDisplay();
        $deployPath = $this->deployer->hosts->get('prod')->getDeployPath();

        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertFileExists($deployPath . '/current/uploaded.html');
    }
}
