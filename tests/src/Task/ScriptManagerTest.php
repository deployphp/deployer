<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Task;

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

    public function testOnce()
    {
        $a = new Task('a');
        $b = new Task('b');
        $b->once();
        $group = new GroupTask('group', ['a', 'b']);

        $taskCollection = new TaskCollection();
        $taskCollection->add($a);
        $taskCollection->add($b);
        $taskCollection->add($group);

        $scriptManager = new ScriptManager($taskCollection);
        self::assertEquals([$a, $b], $scriptManager->getTasks('group'));
        self::assertFalse($a->isOnce());
        self::assertTrue($b->isOnce());

        $group->once();
        self::assertEquals([$a, $b], $scriptManager->getTasks('group'));
        self::assertTrue($a->isOnce());
        self::assertTrue($b->isOnce());
    }

    public function testSelectsCombine()
    {
        $a = new Task('a');
        $b = new Task('b');
        $c = new Task('c');
        $b->select('stage=beta');
        $c->select('stage=alpha|beta & role=db');
        $group = new GroupTask('group', ['a', 'b', 'c']);

        $taskCollection = new TaskCollection();
        $taskCollection->add($a);
        $taskCollection->add($b);
        $taskCollection->add($c);
        $taskCollection->add($group);

        $scriptManager = new ScriptManager($taskCollection);
        self::assertEquals([$a, $b, $c], $scriptManager->getTasks('group'));
        self::assertNull($a->getSelector());

        self::assertEquals([[['=', 'stage', ['beta']]]], $b->getSelector());
        self::assertEquals([[['=', 'stage', ['alpha', 'beta']],['=', 'role', ['db']]]], $c->getSelector());

        $group->select('role=prod');
        self::assertEquals([$a, $b, $c], $scriptManager->getTasks('group'));
        self::assertEquals([[['=', 'role', ['prod']]]], $a->getSelector());
        self::assertEquals([[['=', 'stage', ['beta']]],[['=', 'role', ['prod']]]], $b->getSelector());
        self::assertEquals([[['=', 'stage', ['alpha', 'beta']],['=', 'role', ['db']]],[['=', 'role', ['prod']]]], $c->getSelector());
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
