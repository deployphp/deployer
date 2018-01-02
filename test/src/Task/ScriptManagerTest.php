<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

use Deployer\Component\PharUpdate\Exception\InvalidArgumentException;
use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use PHPUnit\Framework\TestCase;

class ScriptManagerTest extends TestCase
{
    public function testConstructorReturnsScriptManagerInstance()
    {
        $scriptManager = new ScriptManager(new TaskCollection());
        $classname = 'Deployer\Task\ScriptManager';

        $this->assertInstanceOf($classname, $scriptManager);
    }

    public function testThrowsExceptionIfTaskCollectionEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);

        $scriptManager = new ScriptManager(new TaskCollection());
        $scriptManager->getTasks("");
    }

    public function testThrowsExceptionIfTaskDontExists()
    {
        $this->expectException(\InvalidArgumentException::class);

        $taskCollection = new TaskCollection();
        $taskCollection->set('testTask', new Task('testTask'));

        $scriptManager = new ScriptManager($taskCollection);
        $scriptManager->getTasks("testTask2");
    }

    public function testReturnsArrayOnGetTask()
    {
        $hostCollection = new HostCollection();
        $hostCollection->set('app', (new Host('app'))->stage('prod')->roles('app'));
        $hostCollection->set('db', (new Host('db'))->stage('prod')->roles('db'));

        $task = new Task('compile');
        $task
            ->onStage('prod')
            ->onRoles('app');

        $taskCollection = new TaskCollection();
        $taskCollection->set('compile', $task);

        $scriptManager = new ScriptManager($taskCollection, $hostCollection);

        $this->assertNotEmpty($scriptManager->getTasks("compile"));

        $task = new Task('dump');
        $task
            ->onStage('prod')
            ->onRoles('db');

        $taskCollection = new TaskCollection();
        $taskCollection->set('dump', $task);

        $scriptManager = new ScriptManager($taskCollection, $hostCollection);

        $this->assertNotEmpty($scriptManager->getTasks("dump"));
    }
}
