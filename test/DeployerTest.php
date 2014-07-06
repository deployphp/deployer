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
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->exactly(1))
            ->method('callback')
            ->will($this->returnValue(true));

        task('task', function () use ($mock) {
            $mock->callback();
        });

        $appTester = $this->runCommand('task');
    }
}
 