<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Configuration\Configuration;
use Deployer\Console\Application;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class FunctionsTest extends TestCase
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Application
     */
    private $console;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Host
     */
    private $host;

    protected function setUp(): void
    {
        $this->console = new Application();

        $this->input = $this->createMock(Input::class);
        $this->output = $this->createMock(Output::class);
        $this->host = $this->getMockBuilder(Host::class)->disableOriginalConstructor()->getMock();
        $this->host
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn(new Configuration());

        $this->deployer = new Deployer($this->console);
        $this->deployer['input'] = $this->input;
        $this->deployer['output'] = $this->output;
        Context::push(new Context($this->host, $this->input, $this->output));
    }

    protected function tearDown(): void
    {
        Context::pop();
        unset($this->deployer);
        $this->deployer = null;
    }

    public function testHost()
    {
        host('domain.com');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('domain.com'));
        self::assertInstanceOf(Host::class, host('domain.com'));

        host('a1.domain.com', 'a2.domain.com')->set('roles', 'app');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('a1.domain.com'));
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('a2.domain.com'));

        host('db[1:2].domain.com')->set('roles', 'db');
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('db1.domain.com'));
        self::assertInstanceOf(Host::class, $this->deployer->hosts->get('db2.domain.com'));
    }

    public function testLocalhost()
    {
        localhost('domain.com');
        self::assertInstanceOf(Localhost::class, $this->deployer->hosts->get('domain.com'));
    }

    public function testInventory()
    {
        inventory(__DIR__ . '/../fixture/inventory.yml');

        foreach (['app.deployer.org', 'beta.deployer.org', 'db1.deployer.org', 'db2.deployer.org'] as $hostname) {
            self::assertInstanceOf(Host::class, $this->deployer->hosts->get($hostname));
        }
    }

    public function testTask()
    {
        task('task', 'pwd');

        $task = $this->deployer->tasks->get('task');
        self::assertInstanceOf(Task::class, $task);

        $task = task('task');
        self::assertInstanceOf(Task::class, $task);

        task('group', ['task']);
        $task = $this->deployer->tasks->get('group');
        self::assertInstanceOf(GroupTask::class, $task);

        $task = task('callable', [$this, __METHOD__]);
        self::assertInstanceOf(Task::class, $task);
    }

    public function testBefore()
    {
        task('main', 'pwd');
        task('before', 'ls');
        before('main', 'before');

        $names = $this->taskToNames($this->deployer->scriptManager->getTasks('main'));
        self::assertEquals(['before', 'main'], $names);
    }

    public function testAfter()
    {
        task('main', 'pwd');
        task('after', 'ls');
        after('main', 'after');

        $names = $this->taskToNames($this->deployer->scriptManager->getTasks('main'));
        self::assertEquals(['main', 'after'], $names);
    }

    public function testRunLocally()
    {
        $output = runLocally('echo "hello"');
        self::assertEquals('hello', $output);
    }

    private function taskToNames($tasks)
    {
        return array_map(function (Task $task) {
            return $task->getName();
        }, $tasks);
    }
}
