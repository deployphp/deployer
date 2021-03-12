<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use PHPUnit\Framework\TestCase;

class ScriptManagerTest extends TestCase
{
    public function testGetTasks()
    {
        $notify = new Task('notify');
        $info = new GroupTask('info', ['notify']);
        $deploy = new GroupTask('deploy', ['deploy:setup', 'deploy:release']);
        $deploy->addBefore($info);
        $setup = new Task('deploy:setup');
        $release = new Task('deploy:release');

        $taskCollection = new TaskCollection();
        $taskCollection->set($notify->getName(), $notify);
        $taskCollection->set($info->getName(), $info);
        $taskCollection->set($deploy->getName(), $deploy);
        $taskCollection->set($setup->getName(), $setup);
        $taskCollection->set($release->getName(), $release);

        $scriptManager = new ScriptManager($taskCollection);
        self::assertEquals([$notify, $setup, $release], $scriptManager->getTasks('deploy'));
    }

    public function testThrowsExceptionIfTaskCollectionEmpty()
    {
        self::expectException(\InvalidArgumentException::class);

        $scriptManager = new ScriptManager(new TaskCollection());
        $scriptManager->getTasks('');
    }

    public function testThrowsExceptionIfTaskDontExists()
    {
        self::expectException(\InvalidArgumentException::class);

        $taskCollection = new TaskCollection();
        $taskCollection->set('testTask', new Task('testTask'));

        $scriptManager = new ScriptManager($taskCollection);
        $scriptManager->getTasks('testTask2');
    }
}
