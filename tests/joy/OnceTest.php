<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class OnceTest extends AbstractTest
{
    const RECIPE = __DIR__ . '/recipe/once.php';

    public function testOnce()
    {
        $this->dep(self::RECIPE, 'test_once');

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertTrue(substr_count($display, 'SHOULD BE ONCE') == 1, $display);
    }
}
