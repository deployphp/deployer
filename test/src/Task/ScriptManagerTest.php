<?php

namespace Deployer\Task;

use Deployer\Host\Host;
use Deployer\Host\HostCollection;
use Deployer\Component\PharUpdate\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ScriptManagerTest extends TestCase
{
    public function testConstructorReturnsScriptManagerInstance()
    {
        $scriptManager = new ScriptManager(new TaskCollection());
        $classname = 'Deployer\Task\ScriptManager';

        $this->assertInstanceOf($classname, $scriptManager);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionIfTaskCollectionEmpty()
    {
        $scriptManager = new ScriptManager(new TaskCollection());
        $scriptManager->getTasks("");
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionIfTaskDontExists()
    {
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