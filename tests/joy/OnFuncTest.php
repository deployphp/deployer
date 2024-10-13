<?php

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace joy;

class OnFuncTest extends JoyTest
{
    protected function recipe(): string
    {
        return <<<'PHP'
            <?php
            namespace Deployer;
            localhost('prod');
            localhost('beta');

            task('test', [
                'first',
                'second',
            ]);

            task('first', function () {
                set('foo', '{{alias}}');
            });

            task('second', function () {
                on(selectedHosts(), function () {
                    writeln('foo = {{foo}}');
                }); 
            })->once();
            PHP;
    }

    public function testOnFunc()
    {
        putenv('DEPLOYER_LOCAL_WORKER=false');
        $this->dep('test');
        putenv('DEPLOYER_LOCAL_WORKER=true');

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);
        self::assertStringContainsString('[prod] foo = prod', $display);
        self::assertStringContainsString('[beta] foo = beta', $display);
    }
}
