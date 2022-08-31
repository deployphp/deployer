<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class OncePerNodeTest extends AbstractTest
{
    const RECIPE = __DIR__ . '/recipe/once_per_node.php';

    public function testOnce()
    {
        $this->dep(self::RECIPE, 'test_once_per_node');

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('alias: group_a_1 hostname: localhost', $display);
        self::assertStringNotContainsString('alias: group_a_2 hostname: localhost', $display);
        self::assertStringContainsString('alias: group_b_1 hostname: group_b_1', $display);
        self::assertStringNotContainsString('alias: group_b_2 hostname: group_b_2', $display);
    }
}
