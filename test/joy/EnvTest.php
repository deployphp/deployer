<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class EnvTest extends AbstractTest
{
    const RECIPE = __DIR__ . '/recipe/env.php';

    public function testOnce()
    {
        $this->init(self::RECIPE);
        $this->tester->run(['test', '-f' => self::RECIPE]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('global=global', $display);
        self::assertStringContainsString('local=local', $display);
    }
}
