<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\DeployerTester;

class ReferenceTaskTest extends DeployerTester
{
    public function testRun()
    {
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->once())
            ->method('callback')
            ->will($this->returnValue(true));

        task('task', function () use ($mock) {
            $mock->callback();
        });

        task('reference', 'task');

        $this->runCommand('reference');
    }
}
 