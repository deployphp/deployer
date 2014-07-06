<?php
/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\DeployerTester;

class GroupTaskTest extends DeployerTester
{
    public function testRun()
    {
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->exactly(3))
            ->method('callback')
            ->will($this->returnValue(true));

        task('task1', function () use ($mock) {
            $mock->callback();
        });

        task('task2', function () use ($mock) {
            $mock->callback();
        });

        task('group', ['task1', 'task2', function () use ($mock) {
            $mock->callback();
        }]);

        $this->runCommand('group');
    }

    public function testAfter()
    {
        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->exactly(2))
            ->method('callback')
            ->will($this->returnValue(true));

        task('task1', function () {
        });

        task('task2', function () {
        });

        task('group', ['task1', 'task2']);

        after('task1', function () use($mock) {
            $mock->callback();
        });

        task('after', function () use($mock) {
            $mock->callback();
        });

        after('task1', 'after');

        $this->runCommand('group');
    }
}
 