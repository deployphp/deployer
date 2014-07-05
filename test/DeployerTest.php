<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

class DeployerTest extends DeployerTester
{
    public function testRun()
    {
        task('task', function () {
            writeln('task');
        });

        $appTester = $this->runCommand('task');
        $this->assertEquals("task\n", $appTester->getDisplay());
    }
}
 