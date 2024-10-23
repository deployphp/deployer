<?php

declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace joy;

class HostDefaultConfigTest extends JoyTest
{
    protected function recipe(): string
    {
        return <<<'PHP'
            <?php
            namespace Deployer;
            localhost();

            task('test', function () {
                $port = currentHost()->getPort();
                writeln(empty($port) ? 'empty' : "port:$port");
            });
            PHP;
    }

    public function testOnFunc()
    {
        $this->dep('test');
        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('empty', $display);
    }
}
