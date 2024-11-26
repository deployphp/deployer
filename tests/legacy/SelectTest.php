<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Symfony\Component\Console\Output\Output;

class SelectTest extends AbstractTest
{
    public const RECIPE = __DIR__ . '/recipe/select.php';

    public function testSelect()
    {
        $this->init(self::RECIPE);
        $this->tester->run([
            'test',
            '-f' => self::RECIPE,
            'selector' => 'prod',
        ], [
            'verbosity' => Output::VERBOSITY_DEBUG,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringNotContainsString('executing on prod', $display);
        self::assertStringContainsString('executing on beta', $display);
        self::assertStringContainsString('executing on dev', $display);
    }
}
