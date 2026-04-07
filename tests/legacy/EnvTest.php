<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use joy\JoyTest;

class EnvTest extends JoyTest
{
    public const RECIPE = __DIR__ . '/recipe/env.php';

    public function testOnce()
    {
        $this->depFile(self::RECIPE, 'test');

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('global=global', $display);
        self::assertStringContainsString('local=local', $display);
        self::assertStringContainsString('dotenv=Hello, world!', $display);
        self::assertStringContainsString('dotenv=local', $display);
    }
}
